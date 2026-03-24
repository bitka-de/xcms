# Media Library

The Media Library is the central hub for managing all digital assets in xcms — images, videos, audio files, and documents. It provides comprehensive organization, tagging, filtering, and rights management.

---

## Quick Start

**URL:** `/admin/media`

### Uploading Media

1. Click the **Upload Media** button in the top-right corner
2. Select a file (images, audio, video, or documents supported)
3. Choose a folder (optional)
4. Enter a display filename and title
5. Click **Upload**

After upload, you will be redirected to the edit form where you can add tags, copyright information, and metadata.

### Finding Media

Use the **search bar** or **advanced filters** (folder, tag, type) to locate media quickly. Live filtering updates results instantly as you type or select options.

---

## Media Library (`/admin/media`)

The media library displays all uploaded assets in a grid view with preview thumbnails, metadata, and quick actions.

### Grid View Features

Each media card shows:

- **Preview thumbnail** (image, video, audio icon, or document badge)
- **Title** and file type badge (Image, Video, Audio, Document)
- **File size** and MIME type
- **Folder** location
- **Tags** (if assigned)
- **Copyright & license info** (if set)
- **Edit** and **Delete** action buttons

### Search Library

The live search bar at the top allows full-text search across:

- Filename
- Original filename
- Title
- Alt text
- MIME type
- Copyright text and author
- License name and URL
- Source URL
- Tag names and slugs

**Minimum 3 characters** required for search queries. Results update in real time.

### Advanced Filters

Click **Advanced Filters** to reveal filtering options:

| Filter | Effect |
|---|---|
| **Folder** | Show only media in a specific folder (and subfolders) |
| **Tag** | Show only media tagged with a specific tag |
| **Type** | Filter by media type: Image, Video, Audio, or Document |

Filters apply instantly. Combine multiple filters for precise results. All active filters are displayed as removable chips at the bottom of the toolbar.

### Storage Quota

A **Storage** indicator (top-right) shows:

- **Used** storage
- **Remaining** storage
- **Total** quota (5 GB default)
- **Usage percentage** with visual progress bar
- **Breakdown by type** (expandable details)

Once quota is full, new uploads are disabled. Delete media files to free space.

---

## Media Folders (`/admin/media/folders`)

Media can be organized into custom folder hierarchies. Folders are **optional** — media can exist at the root level or be nested within folders.

**URL:** `/admin/media/folders`

### Folder Structure

Folders can have **parent folders**, allowing unlimited nesting. A folder's media count is shown on each folder card.

### Managing Folders

#### Create a Folder

1. Click **Create Folder** in the main panel
2. Enter a folder name
3. (Optional) Select a parent folder
4. Click **Create**

#### Edit a Folder

1. Click the **Manage** button on a folder card
2. Change the name and/or parent folder
3. Click **Save**

**Note:** A folder cannot be its own parent, and a folder cannot be moved under its own descendant.

#### Delete a Folder

1. Click the **Manage** button on a folder card
2. Click **Delete Folder**

**Constraints:**
- Folders with child folders cannot be deleted
- Folders with media items cannot be deleted

To delete a folder with contents, first move/delete all child folders and media items.

#### Reorder Folders

Within a folder hierarchy level, drag folder cards to rearrange them. The new order is saved automatically.

---

## Editing Media (`/admin/media/edit?id=...`)

Click **Edit** on any media card to open the detailed edit form. The form is divided into sections:

### Organization

**Folder:** Choose which folder (if any) the media belongs to.

**Tags:** Assign up to **3 tags** to categorize and filter the media.
- Tags are **auto-created** on first use
- Type a tag name and press **Enter**, **Comma**, or select from suggestions
- Existing tags appear as **autocomplete suggestions** as you type
- Press **Backspace** with an empty input to remove the last tag
- Remove individual tags by clicking the **×** button on each chip

**Tag Rules:**
- Maximum **3 tags per medium**
- Tagging is **case-insensitive** (duplicates removed on save)
- **Unused tags** are automatically deleted from the database

### Metadata

**Display Filename** (required): The admin-facing name of the file (separate from storage name).

**Title** (required): The public/display title of the media, used in admin contexts and visibility references.

**Alt Text** (optional): Descriptive text for accessibility (screen readers, image search). Highly recommended for images.

### Copyright & License

**Copyright Text** (optional): Full legal copyright notice (e.g., "© 2026 Example Studio. All rights reserved.")

**Copyright Author** (optional): Name of the copyright holder or creator.

**License Name** (optional): License identifier (e.g., "CC BY 4.0", "All Rights Reserved").

**License URL** (optional): URL to the license terms or deed.

**Source URL** (optional): Original source or attribution link.

**Usage Notes** (optional): Internal notes on restrictions, required attribution format, or other usage guidance.

**Attribution Required** (checkbox): Mark if public-facing attribution is mandatory when this media is used.

### File Maintenance

**Rename Physical File on Disk:** Opt-in operation to rename the underlying file in storage. This updates the storage path but is transparent to references using the public URL.

---

## Tags

Tags are a lightweight categorization system. They are **automatically created** on first use and **automatically deleted** when no longer assigned to any media.

### Tag Behavior

- **Case-insensitive deduplication:** "Hero", "hero", and "HERO" are treated as the same tag
- **Unique by name:** Only one tag with a given name can exist
- **Slugified URLs:** Tags are automatically assigned URL-safe slugs (e.g., "Hero Tag" → `hero-tag`)
- **Filter by tag:** Use the **Tag** filter in Advanced Filters to show only media with a specific tag
- **Autocomplete:** When editing a media item, existing tags appear as suggestions

### Tag Limits

- **Maximum 3 tags per medium** — enforced both client-side and server-side
- Attempting to add a 4th tag silently fails; suggestions are hidden when the limit is reached

### Orphan Tag Cleanup

Unused tags are automatically removed from the database:
- When a media item's tags are changed (removal triggers cleanup)
- When the media library page is loaded (periodic cleanup)
- Stale tag assignments (orphaned by media deletion) are also cleaned

---

## Media Types

xcms supports the following media types:

| Type | Extensions | Icon |
|---|---|---|
| **Image** | jpg, jpeg, png, gif, webp, svg | Thumbnail preview |
| **Video** | mp4, webm, ogg, mov | Video player icon |
| **Audio** | mp3, wav, ogg, aac, m4a, flac | Audio waveform icon |
| **Document** | pdf, doc, docx, txt, xlsx, xls | File type badge |

Each media item is automatically categorized based on its file extension and MIME type.

---

## Storage & File Management

### File Upload Process

1. File is validated (extension, size, MIME type)
2. File is stored in `/public/uploads/media/` with a uniquely generated server-side name
3. **Display filename** is separate from the **storage filename** — you can rename the display name without affecting storage
4. A **public URL** is generated (e.g., `/uploads/media/abc123xyz.jpg`)

### File Constraints

- **Maximum file size:** 5 GB per file (default, configurable)
- **Storage quota:** 5 GB total (default, configurable)
- **Allowed file types:** Configured in storage service; upload errors will indicate unsupported types

### Deleting Media

1. Click **Delete** on the media card
2. Confirm the action
3. The physical file is deleted from disk
4. All associated tag assignments are removed
5. Unused tags are cleaned up

### Renaming Files

The **display filename** and **storage filename** are separate:

- **Display Filename** (in edit form): Used in admin UI and database records. Change this freely; it doesn't affect the physical file.
- **Storage Filename**: The actual file name on disk. To rename it, check the **"Rename physical file on disk"** option in the edit form and save. Existing public references will still work because xcms uses the database record, not the filename.

---

## Technical Details

### Database Structure

Media is stored across these tables:

- **`media`**: Core media record (title, filename, type, path, etc.)
- **`media_folders`**: Folder definitions with parent-child relationships
- **`media_tags`**: Tag definitions (name, slug)
- **`media_tag_assignments`**: Junction table linking media to tags

### API & Integration

Media public URLs are served at `/uploads/media/{filename}`. Use the **public URL** field in the edit form when referencing media in page blocks or collection JSON.

Example reference in page blocks:
```json
{
  "type": "image",
  "path": "/uploads/media/hero-image-2024.jpg",
  "alt": "Hero image"
}
```

### Performance Notes

- Media list is paginated and loaded on-demand
- Advanced filters apply server-side (database queries)
- Live search applies client-side filtering (no request delay)
- Tag suggestions are preloaded during media edit

---

## Troubleshooting

### Tags not appearing in filter
- Ensure at least one media item is tagged with that tag
- If a tag was created but never assigned, it will be cleaned up automatically
- Reload the page to refresh the tag list

### Upload fails with quota error
- Check the Storage indicator; you may have exceeded 5 GB
- Delete unused media to free space
- Contact the administrator if quota needs to be increased

### Media appears in edit but not in library
- Verify the media has a valid **Title** and **Filename**
- Check that the media's folder still exists (if one was assigned)
- Confirm the media type is supported

### Tags show duplicates or inconsistent casing
- Tags are deduplicated case-insensitively on save
- Reload the page to see the latest tag list
- The tag with the most recently assigned casing will be stored

---

## Best Practices

1. **Use descriptive titles:** "Hero image 2024" is better than "image1"
2. **Set Alt Text for all images:** Improves accessibility and SEO
3. **Tag consistently:** Use the same capitalization (system will deduplicate)
4. **Organize with folders:** Reflect your content structure
5. **Track copyright early:** Add copyright/license info at upload time
6. **Keep filenames clean:** Avoid special characters; stick to letters, numbers, hyphens
7. **Monitor storage:** Check quota regularly to avoid unexpected upload blocks
