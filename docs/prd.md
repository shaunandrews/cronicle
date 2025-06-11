### Product Requirements Document — *Codename: Cronicle*

---

#### 1. Purpose & Vision  
Empower solo bloggers to draft, refine, schedule, and publish WordPress posts through natural-language conversations with an AI assistant that is seamlessly embedded in the standard WordPress editing experience.

#### 2. Target Audience  
- **Primary:** Independent/solo bloggers who manage their own WordPress site.  
- **Secondary:** Small-team content creators who need lightweight AI help without complex workflows.

#### 3. Goals & Success Metrics  

| Goal | Metric (90-day target) |
|------|------------------------|
|Reduce time from idea → publish|≥ 30 % faster average post-creation time (self-reported)|
|Improve publishing consistency |≥ 2× increase in scheduled posts per user|
|Perceived “native WP feel”     |≥ 80 % user satisfaction in post-launch survey|

#### 4. Core Use Cases / User Stories  
1. **Prompt-to-Draft** – *As a blogger, I type “Write a 700-word intro to cold-brew coffee” and receive a saved draft in seconds.*  
2. **Inline Iteration** – *While editing, I highlight a paragraph and ask “shorten this to 3 sentences”; AI rewrites in-place.*  
3. **Series Planner** – *I ask “Generate 5 post outlines for a week-long cold-brew series”; five drafts appear in the Posts list, each tagged and scheduled sequentially.*  
4. **One-click Schedule/Publish** – *After polishing, I tell the assistant “Schedule for next Tuesday at 9 am” or “Publish now”; it updates post status accordingly.*

#### 5. Key Features  
1. **Conversational Drafting Panel**  
   - Sidebar panel inside the Block Editor (extends `@wordpress/edit-post`).  
   - Uses OpenAI (or pluggable provider) via server-side calls to avoid exposing keys client-side.  
2. **Inline Block Actions**  
   - “Rewrite”, “Summarize”, “Expand” quick actions on selected blocks via `RichTextToolbarButton`.  
3. **Bulk Draft Generator**  
   - Modal where user specifies a topic + number of posts → creates multiple `draft` posts via WP REST API.  
4. **Smart Scheduling**  
   - Suggests publish dates/times (spaced evenly or based on user-defined cadence).  
   - Executes via `wp_insert_post` and native scheduling (status `future`, uses WP-Cron).  
5. **Native Look & Feel**  
   - Adheres to `@wordpress/components` design tokens and color palette.  
   - Respects Editor color scheme and accessibility guidelines.  

#### 6. Functional Requirements  
- **Auth & Permissions:** Only users with `edit_posts` capability see the assistant.  
- **Rate Limits:** Per-site quota with graceful error handling and upgrade prompt.  
- **Extensibility Hooks:**  
  - `wp_post_assist_prompt` filter to modify AI prompt before send.  
  - `wp_post_assist_draft_created` action after AI content saved.  
- **i18n Ready:** All strings wrapped in `__()`.

#### 7. Non-Functional Requirements  
- **Performance:** Panel loads < 200 ms; no blocking calls in the editor thread.  
- **Privacy:** Clear notice of content sent to third-party AI; settings screen to opt-out.  
- **Security:** Server-side proxy sanitizes user input; follows WP nonce + capability checks.  
- **Updates:** Uses WordPress auto-update mechanism; semantic versioning.

#### 8. Out-of-Scope (v1)  
- Multi-site network management.  
- Long-form image generation or featured-image creation.  
- Complex editorial workflows (multi-author review lanes).

#### 9. Dependencies  
- WordPress 6.5 +.  
- PHP 8.1 +.  
- OpenAI (or compatible) PHP SDK.

#### 10. Milestones (High-Level)  

| Phase   | Duration | Deliverable                                   |
|---------|----------|----------------------------------------------|
|Prototype|2 wks     |Draft panel with prompt-to-draft flow         |
|Beta     |4 wks     |Inline actions, bulk generator, settings UI   |
|Public v1|2 wks     |Hardening, i18n, WP.org submission            |

#### 11. Open Questions  
1. Which AI provider(s) should be supported beyond OpenAI for regional compliance?  
2. How should usage pricing/limits be surfaced to solo bloggers without adding friction?  
3. Should drafts be auto-tagged/categories based on content analysis in v1?
