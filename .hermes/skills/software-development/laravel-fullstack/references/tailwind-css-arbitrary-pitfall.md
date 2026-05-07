# Tailwind Arbitrary Values: CSS vs JSX

## The Pitfall

Tailwind's arbitrary value syntax (e.g., `text-[14px]`, `bg-[#ff0000]`) works in JSX/HTML class attributes but **NOT** in custom CSS selectors.

## What Triggered This

**Session:** HerPanel design improvements (May 2026)
**Context:** Added CSS rule to improve small text readability

```css
/* resources/css/app.css */
.text-xs, .text-[9px], .text-[10px], .text-[11px] {
    font-weight: 500;
    letter-spacing: 0.015em;
}
```

**Build error:**
```
[plugin vite:css-post]
SyntaxError: [lightningcss minify] No qualified name in attribute selector: 
Dimension { has_sign: false, value: 9.0, int_value: Some(9), unit: "px" }.
1991 |  .text-xs, .text-[9px], .text-[10px], .text-[11px] {
     |                   ^
```

## Why It Fails

1. **Tailwind arbitrary values are runtime utilities**, not valid CSS syntax
2. **lightningcss** (Vite's CSS minifier) tries to parse `.text-[9px]` as CSS selector
3. Square brackets in selectors have specific meaning (attribute selectors like `[type="text"]`)
4. The parser expects valid attribute syntax, not arbitrary Tailwind notation

## The Fix

### Option 1: Remove Invalid Selectors

```css
/* ✅ Keep only standard CSS classes */
.text-xs {
    font-weight: 500;
    letter-spacing: 0.015em;
}
```

Then apply the style in JSX:
```jsx
<div className="text-xs font-medium">...</div>
```

### Option 2: Use @apply in Tailwind Layer

```css
/* resources/css/app.css */
@layer components {
  .stat-label {
    @apply text-[10px] font-semibold tracking-wider;
  }
}
```

Then use the custom class:
```jsx
<div className="stat-label">CPU Usage</div>
```

### Option 3: Configure in tailwind.config.js

```javascript
// tailwind.config.js
export default {
  theme: {
    extend: {
      fontSize: {
        'xs-plus': '10px',
        'sm-plus': '11px',
      }
    }
  }
}
```

Then use in JSX:
```jsx
<div className="text-xs-plus font-medium">...</div>
```

## Where Arbitrary Values DO Work

✅ **JSX/HTML class attributes:**
```jsx
<div className="text-[10px] bg-[#ff0000] px-[18px]">
  Direct in component
</div>
```

✅ **Tailwind @apply directive:**
```css
@layer components {
  .custom-style {
    @apply text-[10px] mt-[15px];
  }
}
```

❌ **CSS selectors:**
```css
/* This FAILS */
.text-[10px] { ... }
div[class*="text-["] { ... }
```

## Root Cause Chain

1. Tried to style multiple Tailwind arbitrary classes at once
2. Wrote CSS selector matching those class names
3. lightningcss parser rejected invalid selector syntax
4. Build failed

## Prevention

- **Never** write CSS selectors that include Tailwind arbitrary notation
- Use `@apply` if you need to compose arbitrary values into reusable styles
- Or configure named utilities in `tailwind.config.js` instead
- Arbitrary values are for inline use in markup, not CSS files

## Related Issues

- Works in development mode (no minification)
- Fails in production build (lightningcss minify)
- Same issue affects all arbitrary Tailwind syntax: `[...]`, not just font sizes

## Docs Reference

- Tailwind Arbitrary Values: https://tailwindcss.com/docs/adding-custom-styles#using-arbitrary-values
- Note: "arbitrary values can be used in class attributes" — does NOT mean CSS selectors
