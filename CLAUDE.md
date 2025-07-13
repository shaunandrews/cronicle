# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Cronicle is a WordPress plugin that provides an AI-powered content assistant for WordPress sites. It enables users to draft, refine, schedule, and publish posts through natural-language conversations with Anthropic's Claude AI. The plugin integrates seamlessly into the WordPress admin interface.

## Architecture

### Modular Component Structure
The plugin follows a separation-of-concerns architecture with specialized classes:

- **`Cronicle_Admin_Main_Refactored`** (`includes/admin/class-cronicle-admin-main-refactored.php`) - Main orchestrator that initializes and coordinates all components
- **`Cronicle_Router`** (`includes/admin/class-cronicle-router.php`) - Handles admin menu registration and page routing
- **`Cronicle_UI`** (`includes/admin/class-cronicle-ui.php`) - Manages UI rendering and view template loading
- **`Cronicle_Enqueue`** (`includes/admin/class-cronicle-enqueue.php`) - Handles script/style enqueueing and localization
- **`Cronicle_Chat_Handler`** (`includes/admin/class-cronicle-chat-handler.php`) - Processes AJAX requests and AI interactions
- **`Cronicle_API_Client`** (`includes/class-cronicle-api-client.php`) - Manages Anthropic API communication
- **`Cronicle_Admin_Settings`** (`includes/class-cronicle-admin-settings.php`) - Handles plugin settings and API key management

### Dependency Flow
Components are initialized in this order:
1. UI component (independent)
2. Router component (depends on UI)
3. Enqueue and Chat Handler (independent)
4. Main orchestrator coordinates all components

### File Structure
```
plugin/
├── wp-cronicle.php                          # Main plugin bootstrap
├── includes/
│   ├── admin/
│   │   ├── views/
│   │   │   └── main-page.php               # UI template
│   │   ├── class-cronicle-router.php
│   │   ├── class-cronicle-enqueue.php
│   │   ├── class-cronicle-chat-handler.php
│   │   ├── class-cronicle-ui.php
│   │   └── class-cronicle-admin-main-refactored.php
│   ├── class-cronicle-admin-settings.php
│   └── class-cronicle-api-client.php
└── assets/
    ├── css/
    │   └── cronicle-admin.css
    └── js/
        └── cronicle-admin.js
```

## Development Patterns

### WordPress Standards
- All classes follow WordPress coding standards
- Internationalization using `__()` functions with 'cronicle' text domain
- Proper capability checks (`edit_posts`, `manage_options`)
- Security through nonce verification and input sanitization
- Uses WordPress Settings API for options management

### API Integration
- Uses Anthropic Claude API via the `Cronicle_API_Client` class
- API key stored securely in WordPress options
- Global accessor function: `cronicle_api_client()`
- Error handling returns `WP_Error` objects for consistency

### AJAX Patterns
- Main chat interactions via `cronicle_chat_message` action
- Draft revision via `cronicle_revise_draft` action
- All AJAX handlers include nonce verification
- Responses use standardized JSON format with success/error states

## Key Constants
- `CRONICLE_VERSION` - Plugin version for cache busting
- `CRONICLE_PLUGIN_DIR` - Absolute path to plugin directory
- `CRONICLE_PLUGIN_URL` - URL to plugin directory

## User Permissions
- Main interface requires `edit_posts` capability
- Settings page requires `manage_options` capability
- API key configuration restricted to administrators

## Testing and Quality
When making changes:
- Follow WordPress PHP coding standards
- Test AJAX functionality with proper nonce verification
- Verify capability checks are enforced
- Ensure all user-facing strings are internationalized
- Test API error handling scenarios

## Core Functionality
The plugin provides:
1. **Conversational Drafting** - Chat interface for creating content
2. **Draft Management** - Create and revise WordPress draft posts
3. **Content Modes** - Support for different content types (draft, outline, etc.)
4. **API Integration** - Secure communication with Anthropic's Claude API
5. **Settings Management** - Configuration interface for API keys and options