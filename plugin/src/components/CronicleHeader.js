/**
 * Cronicle Header Component
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useCronicle } from '../context/CronicleContext';
import { ACTIONS } from '../context/CronicleContext';
import { startNewSession } from '../utils/api';

const CronicleHeader = ({ isApiConfigured }) => {
  // Only use context when API is configured
  let state = { selectedMode: 'draft' };
  let dispatch = () => {};
  
  if (isApiConfigured) {
    try {
      const context = useCronicle();
      state = context.state;
      dispatch = context.dispatch;
    } catch (error) {
      // Context not available, use defaults
    }
  }
  
  const handleNewSession = async () => {
    if (window.confirm(__('Start a new chat session? This will clear the current conversation.', 'cronicle'))) {
      try {
        const response = await startNewSession();
        if (response.success) {
          dispatch({ type: ACTIONS.SET_SESSION_ID, payload: response.data.session_id });
          dispatch({ type: ACTIONS.CLEAR_MESSAGES });
          
          // Show success message briefly
          dispatch({ 
            type: ACTIONS.ADD_MESSAGE, 
            payload: { 
              type: 'system', 
              content: response.data.message,
              timestamp: new Date().getTime()
            }
          });
          
          setTimeout(() => {
            // Remove system message after 2 seconds (would need additional logic)
          }, 2000);
        }
      } catch (error) {
        alert(__('Could not start new session. Please try again.', 'cronicle'));
      }
    }
  };


  return (
    <div className="cronicle-header">
      <h1>{__('Cronicle AI Assistant', 'cronicle')}</h1>
      <div className="cronicle-header-right">
        {isApiConfigured && (
          <div className="cronicle-session-controls">
            <button 
              type="button" 
              className="button cronicle-new-session-btn"
              onClick={handleNewSession}
            >
              {__('New Chat', 'cronicle')}
            </button>
          </div>
        )}
        <span className={`cronicle-status ${isApiConfigured ? 'connected' : 'disconnected'}`}>
          {isApiConfigured ? __('Connected', 'cronicle') : __('Not Connected', 'cronicle')}
        </span>
      </div>
    </div>
  );
};

export default CronicleHeader;