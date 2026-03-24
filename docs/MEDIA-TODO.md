# Media Library — Feature Roadmap

This document outlines planned enhancements, bug fixes, and improvements for the Media Library system in xcms.

---

## Bulk Actions

### High Priority

- [ ] **Multi-select checkboxes on media cards**
  - Add checkbox to each media card in grid view
  - "Select All" / "Deselect All" buttons in toolbar
  - Show count of selected items

- [ ] **Bulk delete**
  - Delete multiple media items at once
  - Confirmation dialog listing selected files
  - Progress indicator or batch confirmation
  - Automatic cleanup of orphaned tags post-deletion

- [ ] **Bulk move to folder**
  - Select multiple media → choose destination folder
  - Modal/dropdown to select target folder
  - Confirm & execute move
  - Update folder media counts in real-time

- [ ] **Bulk tag assignment**
  - Select multiple media → add/remove tags
  - Modal with tag chip input (max 3 tags per item)
  - Option to replace vs. append tags
  - Show conflicts if items have different tags

### Medium Priority

- [ ] **Bulk edit metadata**
  - Edit copyright author / license name across multiple items at once
  - Mark fields as "apply to all" vs. "skip"

- [ ] **Bulk export/download**
  - Download selected media as ZIP
  - Include metadata in JSON manifest
  - Size limitation (e.g., max 500 MB per export)

---

## Folder Management

### High Priority

- [ ] **Bulk move folders**
  - Select multiple folders → move to new parent in one action
  - Preserve folder structure and child relationships

- [ ] **Folder rename in-place**
  - Click folder name to edit inline (instead of opening Manage modal)
  - Save on blur or Enter key

- [ ] **Drag-and-drop move media between folders**
  - Drag media card onto folder card to move it
  - Visual feedback (drop zone highlight, preview)
  - Undo on failed move

- [ ] **Show folder size/media count in grid**
  - Display media count on folder card badge
  - Optional: recursive count (folder + all subfolders)

### Medium Priority

- [ ] **Empty folder warning**
  - Before deleting a folder, confirm it has no media or children

- [ ] **Folder templates**
  - Pre-defined folder structures (e.g., "By Year", "By Type")
  - Bulk-create folder hierarchies

---

## Search & Filtering

### High Priority

- [ ] **Save filter presets**
  - Named saved filters (e.g., "Images without Alt Text", "Untagged Media")
  - Quick access buttons in toolbar

### Medium Priority

- [ ] **Advanced search operators**
  - `tag:"hero"` — search by exact tag
  - `folder:"name"` — search by folder name
  - `type:image` — filter by type
  - `size:>5MB` — filter by file size
  - `created:>2025-01-01` — filter by date range

- [ ] **Search by metadata**
  - Find media by copyright author, license name, etc.
  - Boolean operators (AND, OR, NOT)

---

## Media Upload Improvements

### High Priority

- [ ] **Drag-and-drop upload area**
  - Drag files directly onto page for upload
  - Shows upload progress with file names

- [ ] **Batch/multiple file upload**
  - Select multiple files at once (file picker or drag)
  - Progress for each file
  - Assign folder + base metadata to all files

- [ ] **Upload resume on failure**
  - Save upload progress
  - Resume interrupted uploads

### Medium Priority

- [ ] **Image optimization on upload**
  - Auto-resize large images
  - Generate thumbnails
  - WebP conversion option

- [ ] **Duplicate detection**
  - Warn if file hash matches existing media
  - Option to skip or overwrite

---

## Media Organization & Workflows

### High Priority

- [ ] **Move media between folders**
  - Bulk move (covered above)
  - Single media move via edit form

- [ ] **Duplicate/copy media**
  - Create a copy of existing media with new filename
  - Preserve metadata or start fresh

### Medium Priority

- [ ] **Media versioning**
  - Keep history of file updates (if file is re-uploaded)
  - Compare versions, revert to previous

- [ ] **Media lifecycle states**
  - Draft / Approved / Archived
  - Archive media instead of deleting (soft delete)

---

## Tags

### High Priority

- [ ] **Tag management UI** (`/admin/media/tags`)
  - List all tags with media count
  - Rename tags (updates all assignments)
  - Delete unused tags with single click
  - Bulk tag merging (e.g., "hero" + "Hero" → single tag)

### Medium Priority

- [ ] **Tag hierarchy / aliases**
  - Nested tags (e.g., "Color / Blue", "Color / Red")
  - Tag aliases (e.g., "Hero" → canonical "hero")

- [ ] **Tag colors**
  - Assign custom colors to tags for visual distinction
  - Color shown in chips and filters

---

## Performance & UX

### High Priority

- [ ] **Lazy load media thumbnails**
  - Load preview images as user scrolls
  - Reduce initial page load time

- [ ] **Pagination or infinite scroll**
  - Load media in chunks (50/100 per page)
  - Replace "show all" approach for large libraries

### Medium Priority

- [ ] **Search indexing**
  - Full-text search index for faster queries
  - Autocomplete for search suggestions

- [ ] **Media library statistics**
  - Dashboard panel: total files, storage used by type, oldest/newest uploads
  - Tag usage stats (most/least used tags)

---

## Metadata & Rights

### High Priority

- [ ] **Alt text generator (AI optional)**
  - Read image and suggest alt text
  - User can accept/edit before saving

### Medium Priority

- [ ] **EXIF data preservation**
  - Extract & display EXIF metadata from images
  - Show camera settings, location, date taken

- [ ] **License templates**
  - Pre-defined license URLs (CC0, CC BY, CC BY-SA, etc.)
  - Auto-fill license URL when name is selected

---

## Integration & API

### Medium Priority

- [ ] **Media API endpoints**
  - `GET /api/media` — list media with filters
  - `GET /api/media/:id` — get media details
  - `POST /api/media` — upload (with auth)
  - Use in frontend page builders or external systems

- [ ] **Media webhooks**
  - Trigger events on media upload, update, delete
  - Send to external services (CDN, image processing, etc.)

### Low Priority

- [ ] **Direct integration with page blocks**
  - Block editor can browse/select media inline
  - Drag media from library into block editor

---

## Accessibility & Mobile

### Medium Priority

- [ ] **Mobile-friendly media grid**
  - Responsive grid (1-2 columns on mobile)
  - Touch-friendly bulk select

- [ ] **Keyboard navigation**
  - Tab through media cards
  - Space/Enter to select, Delete key to remove

- [ ] **Screen reader improvements**
  - Announce bulk select counts
  - Describe media in alt text of cards

---

## Fixes & Tech Debt

### High Priority

- [ ] **Verify tag cleanup is robust**
  - Ensure orphaned tags are always cleaned
  - Test with large datasets
  - Performance: index `media_tag_assignments` table

- [ ] **Test folder deletion edge cases**
  - Can't delete folder with children (OK)
  - Can't delete folder with media (OK)
  - Test cascading deletes

### Medium Priority

- [ ] **Error handling improvements**
  - Better error messages for failed uploads
  - Retry logic for transient failures
  - User-facing error toast notifications

- [ ] **Database query optimization**
  - Profile slow queries (large media libraries)
  - Add indexes on frequently filtered columns
  - Consider pagination for list queries

---

## Documentation

- [ ] **User guide for bulk actions** (once implemented)
- [ ] **Keyboard shortcut reference**
- [ ] **Troubleshooting section** (expand existing MEDIA.md)
- [ ] **API documentation** (once endpoints are built)

---

## Priority Matrix

| Feature | Impact | Effort | Priority |
|---|---|---|---|
| Bulk delete | High | Low | **NOW** |
| Bulk move to folder | High | Medium | **NOW** |
| Tag management UI | Medium | Low | **SOON** |
| Drag-and-drop upload | High | Medium | **SOON** |
| Bulk select UI | High | Low | **NOW** |
| Pagination | Medium | Medium | **SOON** |
| Media versioning | Low | High | Later |
| Tag colors | Low | Low | Later |
| API endpoints | Medium | High | Later |

---

## Notes

- **Bulk operations** should update the UI instantly (no page reload)
- **Confirmation dialogs** needed for destructive actions (delete, move)
- **Progress indicators** for operations that might take time
- **Undo/confirmation** helpful but not required for first MVP of bulk actions
