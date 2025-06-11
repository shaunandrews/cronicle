# Cronicle WordPress Plugin

Cronicle is an AI-powered WordPress content assistant that helps you draft, refine, schedule, and publish WordPress posts through natural-language conversations with Anthropic's Claude AI.

## Installation

1. Upload the `wp-cronicle` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Anthropic API key in **Settings > Cronicle**

## Configuration

### Setting up your Anthropic API Key

1. Get your API key from [Anthropic Console](https://console.anthropic.com/)
2. In your WordPress admin, go to **Settings > Cronicle**
3. Enter your API key in the "Anthropic API Key" field
4. Click "Save Settings"

The API key is stored securely in the WordPress options table and is only accessible by users with `manage_options` capability.

## Security Features

- **Capability Checks**: Only users with `manage_options` capability can access settings
- **Nonce Protection**: All forms use WordPress nonce verification
- **Input Sanitization**: All inputs are properly sanitized using WordPress functions
- **API Key Validation**: Basic format validation for Anthropic API keys
- **Secure Storage**: API keys are stored in the WordPress options table with proper escaping

## WordPress Standards Compliance

- Uses WordPress Settings API for secure option management
- Follows WordPress coding standards and best practices
- Includes proper internationalization support (`__()` functions)
- Uses WordPress-style hooks and filters
- Proper capability and permission checking
- Follows WordPress database interaction patterns

## API Client Usage

The plugin includes a robust API client for interacting with Anthropic's API:

```php
// Get the API client instance
$api_client = cronicle_api_client();

// Check if API is ready
if ($api_client->is_api_ready()) {
    // Generate content
    $response = $api_client->generate_content('Write a blog post about coffee');
    
    if (!is_wp_error($response)) {
        // Use the generated content
        $content = $response['content'][0]['text'];
    }
}
```

## File Structure

```
wp-cronicle/
├── wp-cronicle.php                          # Main plugin file
├── includes/
│   ├── class-cronicle-admin-settings.php   # Settings page implementation
│   └── class-cronicle-api-client.php       # API client for Anthropic
└── README.md                               # This file
```

## Requirements

- WordPress 6.5+
- PHP 8.1+
- Valid Anthropic API key

## Contributing

This plugin follows WordPress coding standards and security best practices. When contributing:

1. Follow WordPress PHP coding standards
2. Use proper sanitization and validation
3. Include capability checks for admin functions
4. Use nonces for form submissions
5. Internationalize all user-facing strings

## License

GPL2 - Same as WordPress 