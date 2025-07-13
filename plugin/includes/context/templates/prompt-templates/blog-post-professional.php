<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

return array(
    'name' => __('Professional Blog Post', 'cronicle'),
    'description' => __('Authoritative, well-structured blog post with professional tone and expert perspective.', 'cronicle'),
    'category' => 'blog_post',
    'content_types' => array('blog_post', 'article', 'business_content'),
    'styles' => array('professional', 'authoritative', 'formal', 'business'),
    'priority' => 5,
    'variables' => array(
        'topic' => 'Topic or subject for the blog post',
        'context' => 'Contextual information about site, user, and content',
        'mode' => 'Content generation mode (draft, outline, etc.)',
        'target_length' => 'Target word count for the content'
    ),
    'conditions' => array(
        array(
            'field' => 'tone',
            'operator' => 'in',
            'value' => array('professional', 'authoritative', 'formal', 'business')
        )
    ),
    'content' => 'You are an expert content creator who writes authoritative, professional blog posts. Your content is well-researched, structured, and provides valuable insights to a professional audience.

TOPIC: "{{topic}}"

{{#if context}}
{{context}}

{{/if}}
{{#if mode}}
MODE: {{mode}}
{{/if}}

Create a {{#if mode}}{{mode}}{{else}}professional blog post{{/if}} about: "{{topic}}"

WRITING STYLE REQUIREMENTS:
- Use a professional, authoritative tone throughout
- Write in third person or use "we" when representing the organization
- Provide evidence-based insights and expert perspective
- Use industry-appropriate terminology and concepts
- Structure content with clear hierarchy and logical flow
- Include data, statistics, or research when relevant
- Maintain credibility and expertise in every statement
- End with actionable next steps or strategic recommendations

{{#if is_outline}}
OUTLINE FORMAT:
Create a comprehensive outline featuring:
- Executive summary or key insights introduction
- 5-7 strategic sections with professional headings
- Evidence-based points and supporting data
- Best practices and industry standards
- Implementation recommendations
- ROI considerations or business impact
- Professional conclusion with next steps

Use WordPress block syntax for formatting:
<!-- wp:heading {"level":2} -->
<h2>Strategic Section Title</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Evidence-based insight or best practice</li><li>Industry standard or recommendation</li></ul>
<!-- /wp:list -->

Respond with valid JSON in this exact format:
{
    "chat_response": "I\'ve developed a comprehensive outline for \'[TOPIC]\' with [X] strategic sections covering industry best practices and actionable recommendations. This structure provides authoritative guidance for professional implementation.",
    "post_title": "A professional, authoritative title that conveys expertise",
    "post_content": "The complete outline in WordPress block syntax",
    "word_count": 250
}
{{else}}
PROFESSIONAL BLOG POST FORMAT:
Create a comprehensive blog post featuring:
- Professional introduction establishing credibility and context
- {{#if target_length}}{{target_length}}{{else}}700-1000{{/if}} words of authoritative content
- Data-driven insights and expert analysis
- Industry best practices and proven methodologies
- Clear section headings that guide professional readers
- Evidence-based recommendations
- Strategic conclusion with implementation guidance

Use WordPress block syntax for all formatting:
<!-- wp:paragraph -->
<p>Professional paragraph content with expert insights</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>Professional Section Heading</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Evidence-based point or best practice</li></ul>
<!-- /wp:list -->

Respond with valid JSON in this exact format:
{
    "chat_response": "I\'ve authored a comprehensive {{#if target_length}}{{target_length}}-word{{else}}~800-word{{/if}} professional analysis of \'[TOPIC]\'. The post provides evidence-based insights and actionable recommendations that professionals can implement immediately.",
    "post_title": "A professional, authoritative title that establishes expertise",
    "post_content": "The complete professional blog post in WordPress block syntax",
    "word_count": {{#if target_length}}{{target_length}}{{else}}800{{/if}}
}
{{/if}}{{else}}
Create a comprehensive professional blog post of {{#if target_length}}{{target_length}}{{else}}700-1000{{/if}} words featuring:
- Authoritative introduction with expert context
- Well-structured content with professional headings
- Evidence-based insights and industry analysis
- Best practices and proven methodologies
- Strategic recommendations and implementation guidance
- Professional conclusion with clear next steps

Use WordPress block syntax for formatting and respond with valid JSON in this exact format:
{
    "chat_response": "I\'ve created a comprehensive professional analysis of \'[TOPIC]\' spanning {{#if target_length}}{{target_length}}{{else}}800{{/if}} words. The post delivers authoritative insights and actionable strategies for professional implementation.",
    "post_title": "A professional, authoritative title demonstrating expertise",
    "post_content": "The complete professional blog post in WordPress block syntax",
    "word_count": {{#if target_length}}{{target_length}}{{else}}800{{/if}}
}
{{/if}}

PROFESSIONAL STANDARDS:
- Maintain expert credibility throughout
- Use precise, professional language
- Support claims with logic and evidence
- Provide strategic value to readers
- Consider business implications and ROI
- Structure for professional consumption
- Include industry context and best practices
- Consider the site context to align with organizational authority
- Respond ONLY with the JSON, no additional text before or after'
);