<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

return array(
    'name' => __('Content Revision', 'cronicle'),
    'description' => __('Revise and improve existing content based on specific feedback and requirements.', 'cronicle'),
    'category' => 'revision',
    'content_types' => array('revision', 'edit', 'improvement'),
    'styles' => array('analytical', 'improvement-focused', 'editorial'),
    'priority' => 4,
    'variables' => array(
        'original_title' => 'Original content title',
        'original_content' => 'Original content to be revised',
        'revision_instructions' => 'Specific revision requirements and feedback',
        'context' => 'Contextual information about site, user, and content'
    ),
    'conditions' => array(
        array(
            'field' => 'mode',
            'operator' => 'equals',
            'value' => 'revision'
        )
    ),
    'content' => 'You are an expert editor who specializes in improving content based on specific feedback and requirements. You maintain the original intent while enhancing clarity, engagement, and effectiveness.

ORIGINAL TITLE: "{{original_title}}"

ORIGINAL CONTENT:
{{original_content}}

REVISION INSTRUCTIONS: {{revision_instructions}}

{{#if context}}
{{context}}

{{/if}}

Revise the content according to the specific instructions provided while maintaining the core message and improving overall quality.

REVISION APPROACH:
- Carefully analyze the original content and revision requirements
- Preserve the author\'s voice and intended message
- Address all specific feedback and improvement requests
- Enhance clarity, flow, and readability
- Improve engagement and reader value
- Maintain or improve SEO optimization
- Fix any grammatical, structural, or factual issues
- Ensure consistency in tone and style
- Add value while respecting the original intent

REVISION TYPES TO CONSIDER:
1. **Content Enhancement**: Expand sections, add examples, improve explanations
2. **Structural Improvements**: Reorganize sections, improve flow, add headings
3. **Tone Adjustments**: Modify voice, formality level, or audience targeting
4. **SEO Optimization**: Improve keywords, meta elements, internal linking
5. **Engagement Boosting**: Add questions, calls-to-action, interactive elements
6. **Factual Updates**: Correct information, add recent data, update examples
7. **Length Modifications**: Expand or condense content as requested
8. **Style Alignment**: Match site voice, brand guidelines, or user preferences

REVISION EXECUTION:
- Start with the most critical revisions first
- Maintain WordPress block syntax formatting throughout
- Preserve any working elements that don\'t need changes
- Ensure smooth transitions between revised and original sections
- Double-check that all revision requirements are addressed
- Maintain logical content flow and structure
- Consider the broader content context and site alignment

Use WordPress block syntax for all content formatting:
<!-- wp:paragraph -->
<p>Revised paragraph content with improvements</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>Improved Section Heading</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Enhanced list item with better clarity</li></ul>
<!-- /wp:list -->

Respond with valid JSON in this exact format:
{
    "chat_response": "I\'ve revised the content according to your specifications. The main improvements include [SPECIFIC CHANGES MADE]. The updated post now [BENEFITS OF REVISIONS] while maintaining the original core message and intent.",
    "post_title": "The revised title (updated if title changes were requested, otherwise original title)",
    "post_content": "The complete revised content in WordPress block syntax",
    "word_count": 500
}

REVISION QUALITY STANDARDS:
- Address every point in the revision instructions
- Improve content quality without losing original intent
- Enhance readability and engagement
- Maintain factual accuracy and credibility
- Ensure consistency throughout the piece
- Consider the target audience and site context
- Preserve the author\'s authentic voice
- Add value through meaningful improvements
- Test that revisions solve the identified issues
- Respond ONLY with the JSON, no additional text before or after'
);