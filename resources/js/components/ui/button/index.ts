import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-[var(--radius-sheet)] text-sm font-semibold transition-[color,background-color,box-shadow,opacity] duration-(--dur-micro) disabled:pointer-events-none disabled:opacity-55 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-hidden focus-visible:outline-2 focus-visible:outline-solid focus-visible:outline-offset-2 focus-visible:outline-ring aria-invalid:outline-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-primary text-primary-foreground hover:bg-primary/85",
        destructive:
          "bg-destructive text-destructive-foreground hover:bg-destructive/90",
        outline:
          "bg-sheet text-foreground shadow-xs ring-1 ring-inset ring-(--control-edge) hover:bg-(--color-paper-2)",
        secondary:
          "bg-(--color-paper-3) text-foreground hover:bg-(--color-rule)",
        ghost:
          "hover:bg-foreground/6",
        link: "text-foreground underline decoration-(--color-rule) underline-offset-4 hover:decoration-current",
      },
      size: {
        "default": "h-9 px-3 has-[>svg]:pr-3 has-[>svg]:pl-2.5",
        "sm": "h-7 gap-1.5 px-2.5 has-[>svg]:pr-2.5 has-[>svg]:pl-2",
        "lg": "h-9 px-4 has-[>svg]:pr-4 has-[>svg]:pl-3",
        "icon": "size-9 rounded-full",
        "icon-sm": "size-7 rounded-full",
        "icon-lg": "size-9 rounded-full",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
