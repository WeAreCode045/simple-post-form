# Simple Post Form - WordPress Form Builder Plugin

A powerful yet easy-to-use WordPress form builder plugin with drag-and-drop functionality and extensive styling options.

## Features

### 🎨 Drag-and-Drop Form Builder
- Intuitive drag-and-drop interface for building forms
- No coding knowledge required
- Real-time form preview

### 📝 Multiple Field Types
- Text Field
- Textarea
- Email
- Phone
- Name
- Number

### 🎯 Field Configuration
Each field can be customized with:
- Field label and name
- Placeholder text
- Required/optional setting
- Width options: 100%, 50%, or 33.3%
- Custom styling for each field

### 🎨 Advanced Field Styling
For each field, you can customize:
- Border color
- Border radius
- Background color
- Text color
- Font size
- Font weight
- Padding

### ⚙️ Form Settings
- Form name (internal use)
- Form title (displayed on frontend)
- Form subject (for email)
- Recipient email address
- Sender name
- Sender email address

### 🔘 Button Customization
Fully customizable submit button with:
- Background color
- Hover background color
- Text color
- Hover text color
- Border radius
- Font size
- Font weight
- Text transform (uppercase, lowercase, capitalize, none)

### 🎨 Modal Button Customization
Customize the button that opens modal forms:
- Custom button text
- Background color and hover color
- Text color and hover color
- Border radius
- Font size and weight
- Padding

### 🖼️ Modal Styling
Control modal appearance:
- Modal width and max-width
- Background color
- Overlay color and opacity
- Border radius
- Padding
- Smooth animations

### 📧 Email Notifications
- Automatic email notifications on form submission
- Customizable sender name and email
- HTML formatted email with all form data
- Custom subject line

### 🎯 Modal Popup Forms
- Display forms in beautiful modal popups
- Fully customizable modal button styling
- Modal appearance customization
- Automatic close after submission
- Mobile responsive modals

### 📋 Shortcode Integration
- Each form generates TWO unique shortcodes:
  - Inline: `[simple_form id="1"]` - Embeds form directly
  - Modal: `[simple_form_modal id="1"]` - Creates a button that opens form in popup
- Easy copy-paste integration
- Use in posts, pages, or widgets

## Installation

1. Upload the plugin files to `/wp-content/plugins/simple-post-form/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Form Builder' in the admin menu to create your first form

## Usage

### Creating a Form

1. Navigate to **Form Builder → Add New** in your WordPress admin
2. Enter form details:
   - Form Name (required)
   - Form Title
   - Form Subject
   - Recipient Email (required)
   - Sender Name
   - Sender Email

3. Configure button styling if desired

4. **Add Fields**: Drag field types from the left sidebar to the form canvas:
   - Text Field
   - Textarea
   - Email
   - Phone
   - Name
   - Number

5. **Edit Fields**: Click the edit icon on any field to customize:
   - Label and name
   - Placeholder text
   - Required status
   - Field width (100%, 50%, or 33.3%)
   - Styling options (colors, fonts, borders, padding)

6. **Reorder Fields**: Drag fields up or down using the handle icon

7. **Save Form**: Click "Save Form" button

8. **Copy Shortcode**: After saving, copy the generated shortcode

### Using the Shortcode

#### Inline Form (Direct Embed)
1. Edit any post or page
2. Paste the shortcode: `[simple_form id="X"]` (replace X with your form ID)
3. Publish or update the page

#### Modal Popup Form
1. Configure modal button and modal styling in the form builder
2. Edit any post or page
3. Paste the modal shortcode: `[simple_form_modal id="X"]` (replace X with your form ID)
4. This creates a button that opens the form in a popup modal when clicked
5. Publish or update the page

### Managing Forms

- View all forms: **Form Builder → All Forms**
- Edit form: Click on form name or "Edit" button
- Delete form: Click "Delete" button (confirms before deleting)
- Each form row shows BOTH shortcodes (inline and modal) for quick copying

## Field Width and Layout

- **100% Width**: Field takes full width (stacked layout)
- **50% Width**: Two fields per row (side-by-side)
- **33.3% Width**: Three fields per row

Fields automatically arrange themselves based on width settings. If widths exceed 100%, a new row is created automatically.

## Database Tables

The plugin creates two custom database tables:

- `wp_spf_forms`: Stores form configurations including:
  - Form settings (name, title, subject, emails)
  - Submit button styling
  - Modal button text and styling
  - Modal popup styling
- `wp_spf_form_fields`: Stores field configurations for each form

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- jQuery (included with WordPress)

## Support

For support, feature requests, or bug reports, please contact:
- Website: https://code045.nl
- Email: info@code045.nl

## Changelog

### Version 1.0.0 (December 19, 2025)
- Initial release
- Drag-and-drop form builder
- 6 field types (text, textarea, email, phone, name, number)
- Extensive styling options for fields and buttons
- Email notifications with custom configuration
- Two shortcode types:
  - Inline form embedding
  - Modal popup forms with trigger button
- Modal button customization (text, colors, hover effects, sizing)
- Modal appearance customization (size, colors, overlay, animations)
- Fully responsive design
- Mobile-friendly modals
- Automatic modal close after form submission
- Smooth animations and transitions

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Code045
https://code045.nl