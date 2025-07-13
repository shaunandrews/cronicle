/**
 * Post Preview Component - Renders post content for preview
 */

const PostPreview = ({ postData }) => {
  if (!postData || !postData.title || !postData.content) {
    return null;
  }

  // Convert WordPress blocks to HTML for preview
  const convertBlocksToHTML = (content) => {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    let result = '';

    const elements = tempDiv.querySelectorAll('*');
    elements.forEach(el => {
      const tagName = el.tagName.toLowerCase();
      const text = el.textContent.trim();

      if (!text) return;

      switch (tagName) {
        case 'h1':
        case 'h2':
        case 'h3':
        case 'h4':
        case 'h5':
        case 'h6':
          result += `<${tagName}>${text}</${tagName}>`;
          break;
        case 'p':
          result += `<p>${text}</p>`;
          break;
        case 'ul':
          result += '<ul>';
          el.querySelectorAll('li').forEach(li => {
            result += `<li>${li.textContent}</li>`;
          });
          result += '</ul>';
          break;
        case 'ol':
          result += '<ol>';
          el.querySelectorAll('li').forEach(li => {
            result += `<li>${li.textContent}</li>`;
          });
          result += '</ol>';
          break;
      }
    });

    // If no structured content found, treat as plain text with line breaks
    if (!result) {
      result = content.replace(/\n/g, '<br>');
    }

    return result;
  };

  const previewHTML = convertBlocksToHTML(postData.content);

  return (
    <div className="cronicle-preview-content">
      <div className="cronicle-preview-post">
        <h1>{postData.title}</h1>
        <div dangerouslySetInnerHTML={{ __html: previewHTML }} />
      </div>
    </div>
  );
};

export default PostPreview;