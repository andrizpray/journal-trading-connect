# Dark Theme UI: Contrast Validation & Readability

## When Applying Custom Dark Themes

When implementing custom dark theme designs (NexPanel, custom palettes, etc.), **validate contrast ratios BEFORE deployment**, not after user complaints.

### Critical Pitfall: Blindly Copying Design Values

**What happened (HerPanel, May 2026):**
- Applied NexPanel design with color palette:
  - `nexText3: #3d5a70` (tertiary text)
  - `nexText2: #7a9bb5` (secondary text)
  - `nexText: #c8d8e8` (primary text)
- Used 9-10px font sizes
- User reported: "ada text yang tidak terlihat jelas" (text not clearly visible)

**Root cause:**
- Original NexPanel design was HTML demo, not tested in real app
- Color `#3d5a70` has **2.4:1 contrast** on `#131c26` background (fails WCAG AA 4.5:1 minimum)
- Small font sizes (9-10px) made poor contrast even worse

### Mandatory Pre-Flight Check

**BEFORE** applying any dark theme, validate:

1. **Calculate contrast ratios** using online tools:
   - WebAIM Contrast Checker: https://webaim.org/resources/contrastchecker/
   - Colour Contrast Analyser (desktop app)

2. **WCAG 2.1 Standards:**
   - **AA (minimum):** 4.5:1 for normal text, 3:1 for large text (18pt+/14pt+ bold)
   - **AAA (enhanced):** 7:1 for normal text, 4.5:1 for large text
   - **Target:** AA minimum for tertiary text, AAA for primary/secondary

3. **Small text requires higher contrast:**
   - 9-10px text needs 7:1+ ratio (treat as AAA requirement)
   - 11-12px text can use 4.5:1 minimum
   - 14px+ can use 3:1 minimum

### Quick Fix Formula

When design fails contrast check:

**Option 1: Increase brightness** (preferred for dark themes)
```css
/* Original failing color */
--text3: #3d5a70; /* 2.4:1 contrast - FAIL */

/* Increase brightness until passing */
--text3: #6b8ba8; /* 5.8:1 contrast - PASS AA */
```

**Option 2: Increase font size**
```jsx
// Before
className="text-[9px] text-nexText3"  // 9px with 2.4:1 = unreadable

// After
className="text-[11px] text-nexText3"  // Larger, but still poor contrast

// Best
className="text-[11px] text-nexText2 font-medium"  // Size + brighter color + weight
```

**Option 3: Add font-weight**
```jsx
// Improves perceived contrast
className="text-[10px] font-semibold"  // 600 weight
className="text-[11px] font-medium"    // 500 weight
```

### Systematic Approach

1. **Audit all text elements:**
   ```bash
   # Search for all text color classes in components
   grep -r "text-nex" resources/js/
   grep -r "text-\[" resources/js/
   ```

2. **Test each combination:**
   - Background color + Text color + Font size
   - Create test card with all variations
   - Screenshot and validate

3. **Document fixes:**
   ```javascript
   // Color palette with contrast ratios
   const colors = {
     nexBg: '#080c10',      // Background
     nexPanel: '#131c26',    // Panel background
     
     // Text colors (contrast on nexPanel)
     nexText: '#e8f2ff',     // 14.2:1 - AAA ✓
     nexText2: '#9db8d0',    // 8.5:1 - AAA ✓
     nexText3: '#6b8ba8',    // 5.8:1 - AA ✓
   };
   ```

### React/Inertia Component Pattern

When creating components with dark theme:

```jsx
// ❌ BAD: No contrast validation
<div className="stat-label text-[9px] text-gray-500">CPU Usage</div>

// ✅ GOOD: Validated + documented
<div className="stat-label text-[10px] text-nexText2 font-semibold">
  {/* text-nexText2 = #9db8d0 = 8.5:1 on nexPanel - AAA compliant */}
  CPU Usage
</div>
```

### Tool Misuse Pitfall: execute_code for File Edits

**DON'T** use `execute_code` with `read_file/write_file` for code modifications:

```python
# ❌ BAD - corrupts files with line numbers
content = read_file('/path/to/file.jsx')['content']
content = content.replace('old', 'new')
write_file('/path/to/file.jsx', content)
```

**Symptom:**
```jsx
     1|     1|import { Link } from '@inertiajs/react';
     2|     2|import { useState } from 'react';
```
Double line numbers → build fails with "Unexpected token"

**FIX:** Use `patch` tool instead:
```xml
<invoke name="patch">
  <parameter name="mode">replace