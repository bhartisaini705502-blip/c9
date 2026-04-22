# Asset Manager - Digital Asset Management System

A comprehensive asset management system integrated with ConnectWith9 for organizing, uploading, and managing digital media and files.

## Features

### 📦 Asset Management
- Upload images, videos, documents, audio files
- Organize assets by category
- Add tags and descriptions
- View asset statistics

### 🎯 File Operations
- Drag & drop upload interface
- Bulk file management
- Quick download access
- File deletion with confirmation

### 📊 Asset Statistics
- Total assets counter
- Files per category breakdown
- File size tracking
- Organization by upload date

### 🔐 Security
- User-specific asset storage
- File type validation
- Session-based authentication
- CSRF protection

## File Structure

```
asset-manager/
├── index.php        # Main asset manager interface
├── delete.php       # Asset deletion API
└── README.md       # This file
```

## Database Schema

The system creates an `assets` table with:
- `id` - Auto-increment primary key
- `user_id` - Owner of the asset
- `filename` - Original file name
- `file_path` - Server path to file
- `file_type` - MIME type
- `file_size` - File size in bytes
- `category` - Asset category (images, videos, etc.)
- `tags` - Comma-separated tags
- `description` - Asset description
- `created_at` - Upload timestamp
- `updated_at` - Last modification timestamp

## Supported File Types

**Images:** JPEG, PNG, GIF, WebP  
**Videos:** MP4, WebM  
**Documents:** PDF, DOC, DOCX, TXT  
**Audio:** MP3 and other audio formats

## Upload Directory

Assets are stored in: `/uploads/assets/`

Directory is created automatically on first upload.

## Usage

### Upload an Asset
1. Navigate to `/ai-seo-tool/asset-manager/`
2. Drag & drop files or click to browse
3. Select category and add tags
4. Click "Upload Asset"

### View Assets
- See all uploaded assets in table format
- Files are sorted by upload date (newest first)
- View file size and category

### Manage Assets
- Download files directly
- Delete assets with confirmation
- Search by filename or category

## API Endpoints

**Upload:** POST `/ai-seo-tool/asset-manager/index.php`
- Requires: `asset_file`, `category`, `tags`, `description`

**Delete:** POST `/ai-seo-tool/asset-manager/delete.php`
- Requires: `id` (asset ID)

## Integration

The Asset Manager is fully integrated with ConnectWith9:
- Uses main header and footer
- Respects user authentication
- Follows site design system
- Mobile responsive layout

## Security Features

- File type validation
- User ownership enforcement
- Session authentication
- Safe file handling
- Sanitized file names

## Limitations

- Maximum file size: PHP upload_max_filesize
- Supported formats only (whitelist)
- User authentication required
- File counts limited to 100 per page (easily modifiable)

## Future Enhancements

- Image crop/resize functionality
- Video thumbnail generation
- Batch operations
- Advanced search/filtering
- Sharing capabilities
- Version control
- Folder organization
- Compression support

## Support

For issues or feature requests, contact the development team.

---

**Last Updated:** March 29, 2026
**Version:** 1.0
**Status:** Production Ready
