<?php

namespace App\Support;

use GdImage;
use Throwable;

/**
 * Shrinks a large image to a WebP thumbnail with GD. Anything it can't or
 * needn't shrink (small images, GIFs, huge or broken files) gets null, and
 * the original is shown instead.
 */
class Thumbnail
{
	public const WIDTH = 640;

	/** Larger images would need too much memory on shared hosting. */
	private const MAX_PIXELS = 40_000_000;

	/**
	 * The thumbnail's WebP bytes, or null.
	 */
	public static function make(string $file, string $mimeType): ?string
	{
		if (! function_exists('imagewebp')) {
			return null;
		}

		$size = @getimagesize($file);

		if ($size === false || $size[0] * $size[1] > self::MAX_PIXELS) {
			return null;
		}

		try {
			$image = self::load($file, $mimeType);

			if ($image === null) {
				return null;
			}

			$image = self::upright($image, $file, $mimeType);

			if (imagesx($image) <= self::WIDTH) {
				return null;
			}

			$height = (int) round(imagesy($image) * self::WIDTH / imagesx($image));
			$thumb = imagescale($image, self::WIDTH, max(1, $height), IMG_BICUBIC);

			if ($thumb === false) {
				return null;
			}

			imagealphablending($thumb, false);
			imagesavealpha($thumb, true);

			ob_start();
			imagewebp($thumb, null, 80);

			return (string) ob_get_clean();
		} catch (Throwable) {
			return null;
		}
	}

	private static function load(string $file, string $mimeType): ?GdImage
	{
		$image = match ($mimeType) {
			'image/jpeg' => @imagecreatefromjpeg($file),
			'image/png' => @imagecreatefrompng($file),
			'image/webp' => @imagecreatefromwebp($file),
			'image/avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($file) : false,
			default => false,
		};

		return $image instanceof GdImage ? $image : null;
	}

	/**
	 * Phone photos are often stored sideways with an EXIF note; browsers honour
	 * it for the original, so the thumbnail has to as well.
	 */
	private static function upright(GdImage $image, string $file, string $mimeType): GdImage
	{
		if ($mimeType !== 'image/jpeg' || ! function_exists('exif_read_data')) {
			return $image;
		}

		$exif = @exif_read_data($file);
		$orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

		$rotated = match ($orientation) {
			3 => imagerotate($image, 180, 0),
			6 => imagerotate($image, -90, 0),
			8 => imagerotate($image, 90, 0),
			default => $image,
		};

		return $rotated instanceof GdImage ? $rotated : $image;
	}
}
