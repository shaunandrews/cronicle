/**
 * Post Actions Component - Handles preview and action buttons for post content
 */

import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useCronicle } from '../context/CronicleContext';
import { ACTIONS } from '../context/CronicleContext';

const PostActions = ({ postData, onPreview, fromHistory }) => {
  const { dispatch } = useCronicle();

  useEffect(() => {
    // Auto-show preview for new post content (only if not from history)
    if (!fromHistory && postData) {
      const timer = setTimeout(() => {
        dispatch({ type: ACTIONS.SET_CURRENT_DRAFT, payload: postData });
        dispatch({ type: ACTIONS.SET_SHOW_PREVIEW, payload: true });
      }, 500);

      return () => clearTimeout(timer);
    }
  }, [postData, fromHistory, dispatch]);

  return (
    <div className="cronicle-post-actions">
      <button
        className="button button-secondary"
        onClick={onPreview}
        style={{
          padding: '8px 16px',
          fontSize: '13px'
        }}
      >
        {__('Preview', 'cronicle')}
      </button>
    </div>
  );
};

export default PostActions;