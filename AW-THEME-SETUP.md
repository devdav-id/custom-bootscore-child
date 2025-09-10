# AW Bootscore Child Theme Setup

Simple setup with one custom file in each `aw-` assets folder.

## File Structure

```
bootscore-child/assets/
├── aw-css/aw-custom.css     # Custom CSS styles
├── aw-js/aw-custom.js       # Custom JavaScript
├── aw-scss/aw-custom.scss   # Custom SCSS (compiled into main.css)
```

## How It Works

1. **SCSS**: Edit `/assets/aw-scss/aw-custom.scss` → Gets compiled into `main.css`
2. **CSS**: Edit `/assets/aw-css/aw-custom.css` → Loads directly
3. **JS**: Edit `/assets/aw-js/aw-custom.js` → Loads directly

## Compilation

To compile SCSS:
```bash
sass assets/scss/main.scss assets/css/main.css
```

Or use VS Code "Live Sass Compiler" extension.

## Usage

The theme automatically loads your custom files. Just edit them and save!
