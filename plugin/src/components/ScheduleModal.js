/**
 * Schedule Modal Component - Date/time picker for scheduling posts
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const ScheduleModal = ({ onConfirm, onCancel }) => {
  const [datetime, setDatetime] = useState('');
  const inputRef = useRef(null);

  useEffect(() => {
    // Focus the input when modal opens
    if (inputRef.current) {
      inputRef.current.focus();
    }

    // Handle escape key
    const handleEscape = (e) => {
      if (e.key === 'Escape') {
        onCancel();
      }
    };

    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }, [onCancel]);

  const handleConfirm = () => {
    if (!datetime) {
      alert(__('Select publish date/time', 'cronicle'));
      return;
    }
    onConfirm(datetime);
  };

  const handleOverlayClick = (e) => {
    if (e.target === e.currentTarget) {
      onCancel();
    }
  };

  return (
    <div className="cronicle-schedule-overlay" onClick={handleOverlayClick}>
      <div className="cronicle-schedule-dialog">
        <label htmlFor="schedule-datetime">
          {__('Select publish date/time', 'cronicle')}
        </label>
        <input
          ref={inputRef}
          id="schedule-datetime"
          type="datetime-local"
          className="cronicle-schedule-input"
          value={datetime}
          onChange={(e) => setDatetime(e.target.value)}
        />
        <div style={{ display: 'flex', gap: '8px', marginTop: '10px' }}>
          <button
            className="button button-primary cronicle-schedule-confirm"
            onClick={handleConfirm}
          >
            {__('Schedule', 'cronicle')}
          </button>
          <button
            className="button cronicle-schedule-cancel"
            onClick={onCancel}
          >
            {__('Cancel', 'cronicle')}
          </button>
        </div>
      </div>
    </div>
  );
};

export default ScheduleModal;