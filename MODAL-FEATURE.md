# Modal Popup Feature

## Overview
The modal feature allows forms to be displayed in popup modals triggered by a customizable button, instead of being embedded directly into the page.

## How It Works

### 1. Form Builder Configuration
When editing a form, you'll find two new sections after the submit button styling:

#### Modal Button Settings
- **Modal Button Text**: Customize the text on the button (default: "Open Form")
- **Background Color**: Button background color
- **Hover Background**: Background color on hover
- **Text Color**: Button text color
- **Hover Text Color**: Text color on hover
- **Border Radius**: Rounded corners for the button
- **Font Size**: Button text size
- **Font Weight**: Button text weight
- **Padding**: Button padding

#### Modal Styling
- **Modal Width**: Width of the modal window (e.g., 600px)
- **Modal Max Width**: Maximum width as percentage (e.g., 90%)
- **Modal Background Color**: Background color of the modal content
- **Overlay Color**: Background color/opacity of the overlay (e.g., rgba(0,0,0,0.75))
- **Border Radius**: Rounded corners for the modal
- **Padding**: Padding inside the modal

### 2. Shortcode Usage

Each form has TWO shortcodes:

**Inline Form:**
```
[simple_form id="1"]
```
Embeds the form directly into the page content.

**Modal Form:**
```
[simple_form_modal id="1"]
```
Creates a button that opens the form in a modal popup.

### 3. User Experience

When using the modal shortcode:
1. A styled button appears on the page with your custom text
2. Clicking the button opens the form in a centered modal popup
3. The modal has a close button (×) in the top-right corner
4. Clicking outside the modal (on the overlay) closes it
5. Pressing the ESC key closes the modal
6. After successful form submission, the modal automatically closes after 2 seconds

### 4. Responsive Design

The modal is fully responsive:
- Desktop: Modal appears at specified width
- Tablet: Modal width adapts to screen size
- Mobile: Modal takes 90% of screen width with adjusted padding
- Touch-friendly close button
- Smooth animations on all devices

### 5. Technical Details

**Files Modified:**
- `includes/class-simple-post-form.php`: Database schema updated, save_form() handles modal data
- `includes/class-simple-post-form-admin.php`: Admin UI with modal configuration fields
- `includes/class-simple-post-form-frontend.php`: New shortcode handler and button rendering
- `assets/js/admin.js`: Form save includes modal data
- `assets/js/frontend.js`: Modal open/close functionality, hover effects
- `assets/css/frontend.css`: Modal styling, animations, responsive design

**Database Columns Added:**
- `modal_button_text`: Stores button text
- `modal_button_styles`: JSON with button styling
- `modal_styles`: JSON with modal appearance settings

## Customization Examples

### Example 1: Call-to-Action Button
- Button Text: "Contact Us Now!"
- Background: #FF6B35 (orange)
- Hover: #FF4500 (darker orange)
- Large padding: 20px 50px
- Font Size: 18px

### Example 2: Subtle Link-Style Button
- Button Text: "Send us a message"
- Background: Transparent or light color
- Border: Add via custom CSS
- Small padding: 8px 20px
- Font Size: 14px

### Example 3: Full-Width Modal
- Modal Width: 1200px
- Max Width: 95%
- More padding for breathing room: 50px

### Example 4: Dark Theme Modal
- Modal Background: #2c3e50 (dark blue-gray)
- Overlay: rgba(0,0,0,0.9) (very dark)
- Adjust form field colors accordingly

## Best Practices

1. **Button Text**: Keep it action-oriented ("Get Started", "Contact Us", "Request Info")
2. **Modal Size**: 600px works well for most forms, adjust based on form length
3. **Colors**: Ensure button stands out but matches your site's color scheme
4. **Mobile**: Test on mobile devices - modal should be easy to use and close
5. **Overlay**: Dark overlays (0.7-0.8 opacity) work best for visibility
6. **Accessibility**: Modal includes proper ARIA labels and keyboard support

## Browser Support

The modal feature works on all modern browsers:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Notes

- The modal uses `fadeIn`/`fadeOut` animations (300ms)
- Body scroll is prevented when modal is open
- Only one modal can be open at a time
- Form validation and submission work identically in both inline and modal versions
