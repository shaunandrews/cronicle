/**
 * Chat Container Component
 */

import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useCronicle } from '../context/CronicleContext';
import { ACTIONS } from '../context/CronicleContext';
import MessageList from './MessageList';
import MessageInput from './MessageInput';
import { loadChatHistory } from '../utils/api';

const ChatContainer = () => {
  const { state, dispatch } = useCronicle();

  useEffect(() => {
    // Load chat history on component mount
    const loadHistory = async () => {
      try {
        const response = await loadChatHistory();
        
        if (response.success && response.data.messages) {
          dispatch({ type: ACTIONS.SET_SESSION_ID, payload: response.data.session_id });
          dispatch({ type: ACTIONS.LOAD_MESSAGES, payload: response.data.messages });
        }
      } catch (error) {
        console.error('Error loading chat history:', error);
      }
    };

    loadHistory();
  }, [dispatch]);

  return (
    <div className="cronicle-chat-container">
      {state.showWelcome && (
        <div className="cronicle-welcome-message">
          {__('Create a new draft with AI', 'cronicle')}
        </div>
      )}
      
      <MessageList messages={state.messages} />
      
      {state.isTyping && (
        <div className="cronicle-typing-indicator">
          {__('Assistant is typing...', 'cronicle')}
        </div>
      )}
      
      <MessageInput />
    </div>
  );
};

export default ChatContainer;