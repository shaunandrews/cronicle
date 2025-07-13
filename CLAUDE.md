# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Cronicle is a WordPress plugin that provides an AI-powered content assistant for WordPress sites. It enables users to draft, refine, schedule, and publish posts through natural-language conversations with Anthropic's Claude AI. The plugin integrates seamlessly into the WordPress admin interface with a modern React-based user interface.

## Architecture

### Hybrid PHP + React Architecture
The plugin uses a modern architecture combining WordPress PHP backend with React frontend:

**Backend (PHP):**
- **`Cronicle_Admin_Main_Refactored`** (`includes/admin/class-cronicle-admin-main-refactored.php`) - Main orchestrator that initializes and coordinates all components
- **`Cronicle_Router`** (`includes/admin/class-cronicle-router.php`) - Handles admin menu registration and page routing
- **`Cronicle_UI`** (`includes/admin/class-cronicle-ui.php`) - Manages UI rendering and view template loading
- **`Cronicle_Enqueue`** (`includes/admin/class-cronicle-enqueue.php`) - Handles React bundle loading and localization
- **`Cronicle_Chat_Handler`** (`includes/admin/class-cronicle-chat-handler.php`) - Processes AJAX requests and AI interactions
- **`Cronicle_API_Client`** (`includes/class-cronicle-api-client.php`) - Manages Anthropic API communication
- **`Cronicle_Admin_Settings`** (`includes/class-cronicle-admin-settings.php`) - Handles plugin settings and API key management

**Frontend (React):**
- **`CronicleApp`** - Root component with API configuration detection
- **`CronicleHeader`** - Header with session controls and mode selector
- **`ChatContainer`** - Chat interface with message history
- **`MessageList/Message`** - Scrollable message display with post actions
- **`MessageInput`** - Auto-growing textarea with send functionality
- **`PreviewPanel`** - Slide-out post preview pane
- **`PostPreview`** - Rendered post content display
- **`ActionButtons`** - Create/publish/schedule actions
- **`ScheduleModal`** - Date/time picker modal

### Component Dependency Flow
**PHP Components:**
1. UI component (independent)
2. Router component (depends on UI)
3. Enqueue and Chat Handler (independent)
4. Main orchestrator coordinates all components

**React Components:**
- React Context (`CronicleProvider`) manages global state
- Components communicate via context and props
- API calls use modern fetch with WordPress AJAX endpoints

### File Structure
```
plugin/
├── wp-cronicle.php                          # Main plugin bootstrap
├── package.json                             # React build dependencies
├── webpack.config.js                        # Build configuration
├── src/                                     # React source files
│   ├── components/                          # React components
│   │   ├── CronicleApp.js                  # Root application
│   │   ├── CronicleHeader.js               # Header controls
│   │   ├── ChatContainer.js                # Chat interface
│   │   ├── MessageList.js & Message.js     # Message display
│   │   ├── MessageInput.js                 # Input handling
│   │   ├── PreviewPanel.js                 # Preview interface
│   │   ├── PostPreview.js                  # Content rendering
│   │   ├── ActionButtons.js                # Post actions
│   │   ├── ScheduleModal.js                # Date picker
│   │   └── PostActions.js                  # Message actions
│   ├── context/
│   │   └── CronicleContext.js              # React Context API
│   ├── utils/
│   │   └── api.js                          # WordPress AJAX helpers
│   └── index.js                            # React entry point
├── includes/
│   ├── admin/
│   │   ├── views/
│   │   │   └── main-page.php               # React root container
│   │   ├── class-cronicle-router.php
│   │   ├── class-cronicle-enqueue.php      # React bundle loading
│   │   ├── class-cronicle-chat-handler.php
│   │   ├── class-cronicle-ui.php
│   │   └── class-cronicle-admin-main-refactored.php
│   ├── class-cronicle-admin-settings.php
│   └── class-cronicle-api-client.php
└── assets/
    ├── css/
    │   └── cronicle-admin.css              # Styles (React compatible)
    └── js/
        ├── cronicle-admin.js               # Built React bundle
        └── cronicle-admin.asset.php       # Webpack asset manifest
```

## Development Patterns

### WordPress Standards (PHP)
- All classes follow WordPress coding standards
- Internationalization using `__()` functions with 'cronicle' text domain
- Proper capability checks (`edit_posts`, `manage_options`)
- Security through nonce verification and input sanitization
- Uses WordPress Settings API for options management

### React Development Patterns
- **Component Architecture**: Functional components with hooks
- **State Management**: React Context API with useReducer for global state
- **Internationalization**: Uses `@wordpress/i18n` for translations
- **Build Process**: Modern webpack build with `@wordpress/scripts`
- **WordPress Integration**: Leverages `wp-element`, `wp-components` packages

### API Integration
- **Backend**: Uses Anthropic Claude API via the `Cronicle_API_Client` class
- **Frontend**: Modern fetch API with WordPress AJAX endpoints
- **API key**: Stored securely in WordPress options, checked via `cronicle_api_client()`
- **Error handling**: Consistent WP_Error objects (PHP) and error states (React)

### AJAX Patterns
- Main chat interactions via `cronicle_chat_message` action
- Draft revision via `cronicle_revise_draft` action
- Post creation/publishing via `cronicle_create_post`, `cronicle_publish_post`, `cronicle_schedule_post`
- All AJAX handlers include nonce verification
- Responses use standardized JSON format with success/error states
- React components use utilities in `src/utils/api.js` for consistent request handling

### Build System
- **Development**: `npm run start` for watch mode with hot reloading
- **Production**: `npm run build` generates optimized bundle
- **Dependencies**: Managed via npm with WordPress-specific packages
- **Output**: Built files in `assets/js/` directory for WordPress loading

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

**PHP Backend:**
- Follow WordPress PHP coding standards
- Test AJAX functionality with proper nonce verification
- Verify capability checks are enforced
- Ensure all user-facing strings are internationalized
- Test API error handling scenarios

**React Frontend:**
- Follow React best practices and hooks patterns
- Test component rendering and state management
- Verify API integration and error handling
- Ensure accessibility and responsive design
- Test across different WordPress admin themes

**Build Process:**
- Run `npm run build` before committing changes
- Test both development (`npm run start`) and production builds
- Verify WordPress script dependencies load correctly
- Check bundle size and performance impact

## Development Workflow

### Making Frontend Changes
1. **Start Development**: `npm run start` for live reloading
2. **Edit Components**: Modify files in `src/components/`
3. **Update State**: Add/modify context in `src/context/CronicleContext.js`
4. **API Changes**: Update `src/utils/api.js` for new endpoints
5. **Build**: `npm run build` for production bundle
6. **Test**: Verify functionality in WordPress admin

### Making Backend Changes
1. **PHP Classes**: Modify classes in `includes/` directory
2. **AJAX Handlers**: Update `class-cronicle-chat-handler.php` for new endpoints
3. **Settings**: Modify `class-cronicle-admin-settings.php` for configuration
4. **API**: Update `class-cronicle-api-client.php` for Anthropic integration

### Adding New Features
1. **Plan Component Structure**: Design React component hierarchy
2. **Backend Support**: Add PHP AJAX handlers if needed
3. **Frontend Implementation**: Create React components and context updates
4. **Integration**: Connect frontend to backend via API utilities
5. **Testing**: Verify full functionality and edge cases

## Core Functionality
The plugin provides:
1. **Conversational Drafting** - React-based chat interface for creating content
2. **Draft Management** - Create and revise WordPress draft posts with live preview
3. **Content Modes** - Support for different content types (draft, outline, etc.)
4. **API Integration** - Secure communication with Anthropic's Claude API
5. **Settings Management** - Configuration interface for API keys and options
6. **Real-time Preview** - Slide-out preview panel with post actions
7. **Post Publishing** - Direct publishing and scheduling from the interface
8. **Session Management** - Chat history persistence and session controls