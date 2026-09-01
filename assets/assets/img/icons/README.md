# Service Icons

This folder should contain the following icon files:

1. **computer-training.png** - Icon for Computer Training service
   - Recommended: 60x60px PNG image
   - Suggested icon: Computer/laptop icon
   - Color scheme: Primary blue (#1e3a8a) or matching brand colors

2. **cyber-cafe.png** - Icon for Cyber Café Services
   - Recommended: 60x60px PNG image
   - Suggested icon: Printer/document icon
   - Color scheme: Primary blue (#1e3a8a) or matching brand colors

3. **government-registration.png** - Icon for Government Registration
   - Recommended: 60x60px PNG image
   - Suggested icon: Document/certificate icon
   - Color scheme: Primary blue (#1e3a8a) or matching brand colors

## Temporary Solution

Until actual icon files are added, you can:
- Use existing images from the `/images` folder as placeholders
- Download free icons from sites like:
  - Flaticon (https://www.flaticon.com/)
  - Icons8 (https://icons8.com/)
  - Font Awesome (https://fontawesome.com/)
- Create custom icons matching the brand style

## Current Implementation

The HTML has been updated to use these icon paths:
- `<img src="assets/img/icons/computer-training.png" alt="Computer Training Icon" class="service-icon">`
- `<img src="assets/img/icons/cyber-cafe.png" alt="Cyber Café Icon" class="service-icon">`
- `<img src="assets/img/icons/government-registration.png" alt="Government Registration Icon" class="service-icon">`

CSS styling applied:
```css
.service-icon {
  height: 60px;
  width: 60px;
  margin-bottom: 20px;
  object-fit: contain;
}

