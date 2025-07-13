# Cronicle Development Guide

## Quick Start

### Prerequisites
- Node.js 16+ and npm
- WordPress development environment
- Basic knowledge of React and WordPress plugin development

### Setup
```bash
cd plugin/
npm install
npm run build
```

## Development Workflow

### Frontend Development (React)
```bash
# Start development server with hot reloading
npm run start

# Build for production
npm run build

# Lint JavaScript
npm run lint:js

# Lint CSS
npm run lint:css
```

### Project Structure
```
plugin/
├── src/                    # React source files
│   ├── components/         # React components
│   ├── context/           # React Context for state management
│   ├── utils/             # API utilities and helpers
│   └── index.js           # React entry point
├── includes/              # PHP backend classes
├── assets/               # Built files and CSS
└── package.json          # Node.js dependencies
```

### Adding New React Components

1. **Create Component File**
   ```javascript
   // src/components/MyNewComponent.js
   import { useState } from '@wordpress/element';
   import { __ } from '@wordpress/i18n';

   const MyNewComponent = ({ prop1, prop2 }) => {
     const [localState, setLocalState] = useState('');

     return (
       <div className="my-new-component">
         <h2>{__('My New Component', 'cronicle')}</h2>
         {/* Component content */}
       </div>
     );
   };

   export default MyNewComponent;
   ```

2. **Add to Parent Component**
   ```javascript
   import MyNewComponent from './MyNewComponent';

   // In parent component's render
   <MyNewComponent prop1="value" prop2={state.value} />
   ```

3. **Update Context if Needed**
   ```javascript
   // src/context/CronicleContext.js
   // Add new action types and reducer cases
   export const ACTIONS = {
     // ... existing actions
     SET_MY_NEW_STATE: 'SET_MY_NEW_STATE',
   };

   // In reducer function
   case ACTIONS.SET_MY_NEW_STATE:
     return {
       ...state,
       myNewState: action.payload,
     };
   ```

### Adding New API Endpoints

1. **Add PHP AJAX Handler**
   ```php
   // includes/admin/class-cronicle-chat-handler.php
   public function register_hooks() {
       // ... existing hooks
       add_action('wp_ajax_cronicle_my_new_action', array($this, 'handle_my_new_action'));
   }

   public function handle_my_new_action() {
       // Verify nonce
       if (!wp_verify_nonce($_POST['nonce'], 'cronicle_chat_nonce')) {
           wp_die('Security check failed');
       }

       // Process request
       $result = $this->process_my_action($_POST);

       // Return JSON response
       wp_send_json($result);
   }
   ```

2. **Add Frontend API Function**
   ```javascript
   // src/utils/api.js
   export const myNewAction = async ({ param1, param2 }) => {
     return makeAjaxRequest({
       action: 'cronicle_my_new_action',
       param1,
       param2
     });
   };
   ```

3. **Use in Component**
   ```javascript
   import { myNewAction } from '../utils/api';

   const handleMyAction = async () => {
     try {
       const response = await myNewAction({ param1: 'value' });
       if (response.success) {
         // Handle success
       }
     } catch (error) {
       // Handle error
     }
   };
   ```

### State Management Patterns

#### Reading State
```javascript
import { useCronicle } from '../context/CronicleContext';

const MyComponent = () => {
  const { state } = useCronicle();
  
  return <div>{state.messages.length} messages</div>;
};
```

#### Updating State
```javascript
import { useCronicle } from '../context/CronicleContext';
import { ACTIONS } from '../context/CronicleContext';

const MyComponent = () => {
  const { state, dispatch } = useCronicle();
  
  const handleAction = () => {
    dispatch({
      type: ACTIONS.ADD_MESSAGE,
      payload: { type: 'user', content: 'Hello' }
    });
  };
  
  return <button onClick={handleAction}>Add Message</button>;
};
```

### CSS and Styling

The plugin uses existing WordPress admin styles with custom CSS. React components use the same CSS classes as the original jQuery implementation for compatibility.

#### Adding New Styles
```css
/* assets/css/cronicle-admin.css */
.my-new-component {
    padding: 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.my-new-component h2 {
    margin: 0 0 15px 0;
    font-size: 18px;
    color: #23282d;
}
```

### Testing

#### Manual Testing Checklist
- [ ] Component renders correctly
- [ ] State updates work as expected
- [ ] API calls succeed and handle errors
- [ ] Internationalization strings display correctly
- [ ] Responsive design works on mobile
- [ ] Accessibility with keyboard navigation
- [ ] WordPress admin theme compatibility

#### Testing New Features
1. **Test API Configuration States**
   - Test with API key configured
   - Test without API key configured
   - Test with invalid API key

2. **Test User Interactions**
   - Send messages
   - Create/publish/schedule posts
   - Preview functionality
   - Session management

3. **Test Error Scenarios**
   - Network failures
   - Invalid responses
   - Permission errors

### Build and Deployment

#### Development Build
```bash
npm run start
# - Includes source maps
# - Hot reloading
# - Development optimizations
```

#### Production Build
```bash
npm run build
# - Minified bundle
# - Optimized for performance
# - Ready for deployment
```

#### WordPress Integration
The build process outputs files to:
- `assets/js/cronicle-admin.js` - React bundle
- `assets/js/cronicle-admin.asset.php` - Webpack asset manifest

These files are automatically loaded by the WordPress enqueue system.

### Common Patterns

#### Loading States
```javascript
const [isLoading, setIsLoading] = useState(false);

const handleAsyncAction = async () => {
  setIsLoading(true);
  try {
    await myAsyncFunction();
  } finally {
    setIsLoading(false);
  }
};

return (
  <button disabled={isLoading}>
    {isLoading ? __('Loading...', 'cronicle') : __('Action', 'cronicle')}
  </button>
);
```

#### Error Handling
```javascript
const [error, setError] = useState(null);

const handleAction = async () => {
  try {
    setError(null);
    const response = await apiCall();
    if (!response.success) {
      setError(response.data?.message || 'Unknown error');
    }
  } catch (err) {
    setError('Network error occurred');
  }
};

return (
  <div>
    {error && <div className="error">{error}</div>}
    <button onClick={handleAction}>Action</button>
  </div>
);
```

#### Conditional Rendering
```javascript
return (
  <div>
    {state.isApiConfigured ? (
      <ChatInterface />
    ) : (
      <SetupNotice />
    )}
  </div>
);
```

### Debugging

#### React DevTools
Install React DevTools browser extension for component inspection and state debugging.

#### Console Debugging
```javascript
// Temporary debugging (remove before commit)
console.log('State:', state);
console.log('API Response:', response);
```

#### WordPress Debug
Enable WordPress debugging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Performance Considerations

#### Optimizing Re-renders
```javascript
// Use React.memo for expensive components
const ExpensiveComponent = React.memo(({ data }) => {
  return <div>{/* expensive rendering */}</div>;
});

// Use useMemo for expensive calculations
const expensiveValue = useMemo(() => {
  return expensiveCalculation(data);
}, [data]);
```

#### Bundle Size
- Keep dependencies minimal
- Use tree shaking with ES6 imports
- Monitor bundle size with webpack analyzer

### Troubleshooting

#### Common Issues

**React Context Error**
```
Error: useCronicle must be used within a CronicleProvider
```
Solution: Ensure component is wrapped in `<CronicleProvider>`

**AJAX Nonce Errors**
```
Security check failed
```
Solution: Verify nonce is properly passed and WordPress session is valid

**Build Errors**
```
Module not found
```
Solution: Check import paths and ensure files exist

**WordPress Script Dependencies**
```
wp is not defined
```
Solution: Verify WordPress script dependencies are properly enqueued

For more help, check the React migration summary in `docs/react-migration-summary.md`.