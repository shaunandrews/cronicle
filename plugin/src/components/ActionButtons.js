/**
 * Action Buttons Component - Handles create, publish, and schedule actions
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useCronicle } from '../context/CronicleContext';
import { ACTIONS } from '../context/CronicleContext';
import { createPost, publishPost, schedulePost } from '../utils/api';
import ScheduleModal from './ScheduleModal';

const ActionButtons = ({ postData }) => {
  const { dispatch } = useCronicle();
  const [isCreating, setIsCreating] = useState(false);
  const [isPublishing, setIsPublishing] = useState(false);
  const [isScheduling, setIsScheduling] = useState(false);
  const [showScheduleModal, setShowScheduleModal] = useState(false);
  const [buttonStates, setButtonStates] = useState({
    created: false,
    published: false,
    scheduled: false
  });

  if (!postData || !postData.title || !postData.content) {
    return null;
  }

  const createButtonText = postData.is_outline 
    ? __('Create Outline Draft', 'cronicle')
    : __('Create Draft Post', 'cronicle');

  const handleCreate = async () => {
    setIsCreating(true);
    
    try {
      const response = await createPost({
        title: postData.title,
        content: postData.content
      });

      if (response.success) {
        const createdText = postData.is_outline 
          ? __('✓ Outline Created', 'cronicle')
          : __('✓ Draft Created', 'cronicle');
        
        setButtonStates(prev => ({ ...prev, created: true }));
        
        // Add success message
        setTimeout(() => {
          dispatch({ 
            type: ACTIONS.ADD_MESSAGE, 
            payload: { 
              type: 'assistant', 
              content: `${response.data.message} Click here to edit it.`,
              timestamp: new Date().getTime(),
              editUrl: response.data.edit_url
            }
          });
        }, 1000);

        // Clear current draft and hide preview
        dispatch({ type: ACTIONS.SET_CURRENT_DRAFT, payload: null });
        dispatch({ type: ACTIONS.SET_SHOW_PREVIEW, payload: false });
      } else {
        alert(`Error creating post: ${response.data?.message || 'Unknown error'}`);
      }
    } catch (error) {
      alert('Error creating post. Please try again.');
    } finally {
      setIsCreating(false);
    }
  };

  const handlePublish = async () => {
    setIsPublishing(true);
    
    try {
      const response = await publishPost({
        title: postData.title,
        content: postData.content
      });

      if (response.success) {
        setButtonStates(prev => ({ ...prev, published: true }));
        
        setTimeout(() => {
          dispatch({ 
            type: ACTIONS.ADD_MESSAGE, 
            payload: { 
              type: 'assistant', 
              content: `${response.data.message} Click here to view it.`,
              timestamp: new Date().getTime(),
              viewUrl: response.data.view_url
            }
          });
        }, 1000);

        dispatch({ type: ACTIONS.SET_CURRENT_DRAFT, payload: null });
        dispatch({ type: ACTIONS.SET_SHOW_PREVIEW, payload: false });
      } else {
        alert(`Error publishing post: ${response.data?.message || 'Unknown error'}`);
      }
    } catch (error) {
      alert('Error publishing post. Please try again.');
    } finally {
      setIsPublishing(false);
    }
  };

  const handleSchedule = async (datetime) => {
    if (!datetime) {
      setShowScheduleModal(false);
      return;
    }

    setIsScheduling(true);
    setShowScheduleModal(false);
    
    try {
      const response = await schedulePost({
        title: postData.title,
        content: postData.content,
        scheduled_datetime: datetime
      });

      if (response.success) {
        setButtonStates(prev => ({ ...prev, scheduled: true }));
        
        setTimeout(() => {
          dispatch({ 
            type: ACTIONS.ADD_MESSAGE, 
            payload: { 
              type: 'assistant', 
              content: `${response.data.message} Click here to edit it.`,
              timestamp: new Date().getTime(),
              editUrl: response.data.edit_url
            }
          });
        }, 1000);

        dispatch({ type: ACTIONS.SET_CURRENT_DRAFT, payload: null });
        dispatch({ type: ACTIONS.SET_SHOW_PREVIEW, payload: false });
      } else {
        alert(`Error scheduling post: ${response.data?.message || 'Unknown error'}`);
      }
    } catch (error) {
      alert('Error scheduling post. Please try again.');
    } finally {
      setIsScheduling(false);
    }
  };

  return (
    <>
      <div className="cronicle-preview-actions">
        <button
          className={`cronicle-preview-create-btn ${buttonStates.created ? 'button-secondary' : ''}`}
          onClick={handleCreate}
          disabled={isCreating || buttonStates.created}
        >
          {isCreating 
            ? (postData.is_outline ? __('Creating Outline...', 'cronicle') : __('Creating Draft...', 'cronicle'))
            : (buttonStates.created 
                ? (postData.is_outline ? __('✓ Outline Created', 'cronicle') : __('✓ Draft Created', 'cronicle'))
                : createButtonText
              )
          }
        </button>
        
        <button
          className={`button button-primary cronicle-preview-publish-btn ${buttonStates.published ? 'button-secondary' : ''}`}
          onClick={handlePublish}
          disabled={isPublishing || buttonStates.published}
        >
          {isPublishing 
            ? __('Publishing...', 'cronicle')
            : (buttonStates.published 
                ? __('✓ Post Published', 'cronicle')
                : __('Publish Now', 'cronicle')
              )
          }
        </button>
        
        <button
          className={`button cronicle-preview-schedule-btn ${buttonStates.scheduled ? 'button-secondary' : ''}`}
          onClick={() => setShowScheduleModal(true)}
          disabled={isScheduling || buttonStates.scheduled}
        >
          {isScheduling 
            ? __('Scheduling...', 'cronicle')
            : (buttonStates.scheduled 
                ? __('✓ Post Scheduled', 'cronicle')
                : __('Schedule', 'cronicle')
              )
          }
        </button>
      </div>

      {showScheduleModal && (
        <ScheduleModal
          onConfirm={handleSchedule}
          onCancel={() => setShowScheduleModal(false)}
        />
      )}
    </>
  );
};

export default ActionButtons;