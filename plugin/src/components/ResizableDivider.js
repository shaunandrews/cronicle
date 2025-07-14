/**
 * Resizable Divider Component - Allows dragging to resize adjacent panels
 */

import { useState, useEffect, useCallback } from '@wordpress/element';

const ResizableDivider = ({ onResize, minWidth = 300, maxWidth = 800 }) => {
  const [isDragging, setIsDragging] = useState(false);
  const [startX, setStartX] = useState(0);
  const [startWidth, setStartWidth] = useState(0);

  const handleMouseDown = (e) => {
    setIsDragging(true);
    setStartX(e.clientX);
    const previewPanel = document.querySelector('.cronicle-preview-container');
    if (previewPanel) {
      setStartWidth(previewPanel.offsetWidth);
    }
    e.preventDefault();
  };

  const handleMouseMove = useCallback((e) => {
    if (!isDragging) return;
    
    const deltaX = startX - e.clientX;
    const newWidth = Math.max(minWidth, Math.min(maxWidth, startWidth + deltaX));
    
    if (onResize) {
      onResize(newWidth);
    }
  }, [isDragging, startX, startWidth, minWidth, maxWidth, onResize]);

  const handleMouseUp = useCallback(() => {
    setIsDragging(false);
  }, []);

  useEffect(() => {
    if (isDragging) {
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
      document.body.style.cursor = 'col-resize';
      document.body.style.userSelect = 'none';
      
      return () => {
        document.removeEventListener('mousemove', handleMouseMove);
        document.removeEventListener('mouseup', handleMouseUp);
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
      };
    }
  }, [isDragging, handleMouseMove, handleMouseUp]);

  return (
    <div 
      className={`cronicle-resize-divider ${isDragging ? 'dragging' : ''}`}
      onMouseDown={handleMouseDown}
    />
  );
};

export default ResizableDivider;