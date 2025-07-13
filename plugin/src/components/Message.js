/**
 * Individual Message Component
 */

import { __ } from '@wordpress/i18n';
import { useCronicle } from '../context/CronicleContext';
import { ACTIONS } from '../context/CronicleContext';
import PostActions from './PostActions';

const Message = ({ message }) => {
  const { dispatch } = useCronicle();

  const handlePreviewClick = () => {
    if (message.post_data) {
      dispatch({ type: ACTIONS.SET_CURRENT_DRAFT, payload: message.post_data });
      dispatch({ type: ACTIONS.SET_SHOW_PREVIEW, payload: true });
    }
  };

  // Handle system messages
  if (message.type === 'system') {
    return (
      <div className="cronicle-message system">
        <div className="cronicle-message-content">
          <em>{message.content}</em>
        </div>
        <div style={{ clear: 'both' }} />
      </div>
    );
  }

  return (
    <>
      <div className={`cronicle-message ${message.type}`}>
        <div className="cronicle-message-content">
          {message.content}
          
          {/* Add preview button if this is post content */}
          {message.is_post_content && message.post_data && (
            <PostActions 
              postData={message.post_data}
              onPreview={handlePreviewClick}
              fromHistory={message.fromHistory}
            />
          )}
        </div>
      </div>
      <div style={{ clear: 'both' }} />
    </>
  );
};

export default Message;