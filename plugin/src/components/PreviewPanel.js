/**
 * Preview Panel Component - Shows post preview with actions
 */

import { __ } from '@wordpress/i18n';
import { useCronicle } from '../context/CronicleContext';
import { ACTIONS } from '../context/CronicleContext';
import PostPreview from './PostPreview';
import ActionButtons from './ActionButtons';

const PreviewPanel = ({ width = 400 }) => {
  const { state, dispatch } = useCronicle();

  const handleClose = () => {
    dispatch({ type: ACTIONS.SET_SHOW_PREVIEW, payload: false });
  };

  if (!state.showPreview || !state.currentDraft) {
    return null;
  }

  const headerText = state.currentDraft.is_outline 
    ? __('Post Outline Preview', 'cronicle')
    : __('Post Preview', 'cronicle');

  return (
    <div 
      className={`cronicle-preview-container ${state.showPreview ? 'active' : ''}`}
      style={{ width: `${width}px` }}
    >
      <div className="cronicle-preview-header">
        <span className="cronicle-preview-title">{headerText}</span>
        <button 
          className="cronicle-preview-close-btn"
          onClick={handleClose}
          aria-label={__('Close preview', 'cronicle')}
        >
          ×
        </button>
      </div>
      
      <PostPreview postData={state.currentDraft} />
      
      <ActionButtons postData={state.currentDraft} />
    </div>
  );
};

export default PreviewPanel;