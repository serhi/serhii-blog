# Last.fm Recent Albums - WordPress Plugin

Display your recent albums from Last.fm with live preview in the block editor.

## Features

- ✅ **Live Preview** - See your albums in real-time while editing
- ✅ **REST API** - Fast, cached API endpoint for album data
- ✅ **Settings Page** - Easy configuration in WordPress admin
- ✅ **Beautiful Card Layout** - Inspired by Rich Tabor's Cards Block design
- ✅ **Standalone** - No dependencies on other plugins
- ✅ **Cached** - 10-minute cache to avoid API rate limits
- ✅ **Adjustable** - Choose 1-12 albums to display

## Installation

### Method 1: Upload Pre-built Plugin (Recommended)

1. Download or create a ZIP file containing:
   - `lastfm-recent-albums.php`
   - `build/` folder (with compiled JS/CSS)
   - `templates/` folder
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Method 2: Manual Installation

1. Download/copy the plugin folder
2. Upload to `wp-content/plugins/lastfm-recent-albums/`
3. Go to **Plugins** in WordPress admin
4. Find "Last.fm Recent Albums" and click **Activate**

### Method 3: Build from Source

If you want to modify the code:

1. Create the plugin folder structure:
```
lastfm-recent-albums/
├── lastfm-recent-albums.php
├── package.json
├── src/
│   ├── index.js
│   └── style.scss
└── templates/
    └── albums-grid.php
```

2. Install dependencies:
```bash
cd wp-content/plugins/lastfm-recent-albums
npm install
```

3. Build the plugin:
```bash
npm run build
```

4. Activate in WordPress admin

## Configuration

### Step 1: Get Your Last.fm API Key

1. Go to: https://www.last.fm/api/account/create
2. Fill out the application form:
   - **Application name**: Your site name
   - **Application description**: Brief description
   - **Callback URL**: Your site URL (or leave blank)
3. Submit and copy your **API Key**

### Step 2: Configure the Plugin

1. In WordPress admin, go to **Settings → Last.fm Albums**
2. Paste your **API Key**
3. Enter your **Last.fm Username** (from your profile URL)
   - If your profile is `https://letterboxd.com/username`
   - Your username is: `username`
4. Click **Save Changes**

## Usage

### Adding the Block

1. Edit any page or post
2. Click the **+** button to add a block
3. Search for **"Last.fm Recent Albums"**
4. Add the block to your page
5. See your albums appear instantly with live preview!

### Adjusting Settings

In the block sidebar (right panel):
- **Number of Albums**: Adjust from 1 to 12 albums
- Changes update in real-time

### Using the Shortcode

The plugin doesn't include a shortcode by default, but the block can be added to any post, page, or widget area that supports blocks.

## Files Structure

### Required Files (For Production)
```
lastfm-recent-albums/
├── lastfm-recent-albums.php    # Main plugin file
├── build/                       # Compiled assets (required)
│   ├── index.js
│   ├── index.asset.php
│   ├── index.css
│   └── style-index.css
└── templates/                   # Frontend templates
    └── albums-grid.php
```

### Development Files (Optional)
```
├── src/                         # Source files for development
│   ├── index.js                # React component
│   └── style.scss              # Styles
├── package.json                # NPM configuration
├── package-lock.json           # NPM lock file
├── node_modules/               # Dependencies (large, not needed for deployment)
└── README.md                   # Documentation
```

## Development

### For Development with Hot Reload:
```bash
npm start
```

### For Production Build:
```bash
npm run build
```

### Linting:
```bash
npm run lint:js
```

## Deployment

### Clean Build for Distribution

Before deploying to another site, delete these files to reduce size:
- `node_modules/` folder
- `package-lock.json`
- `.DS_Store` files
- Any `.log` files

Keep only:
- `lastfm-recent-albums.php`
- `build/` folder
- `templates/` folder

Optional (if you might modify code later):
- `src/` folder
- `package.json`
- `README.md`

## REST API Endpoint

The plugin creates a public REST API endpoint:

```
GET /wp-json/lastfm-albums/v1/albums?limit=4
```

**Parameters:**
- `limit` (integer, optional): Number of albums to return (default: 4)

**Response:**
```json
[
  {
    "name": "Album Name",
    "artist": "Artist Name",
    "image": "https://...",
    "url": "https://last.fm/..."
  }
]
```

This endpoint can be used by external applications or custom JavaScript.

## Caching

Album data is cached for **10 minutes** to:
- Improve performance
- Reduce API calls
- Avoid Last.fm rate limits

The cache is automatically refreshed after 10 minutes.

## Troubleshooting

### "Last.fm API key or username not configured"
- Go to **Settings → Last.fm Albums**
- Verify your API key and username are correct
- Save changes

### No albums showing
- Check that you have recently scrobbled music on Last.fm
- Verify your Last.fm profile is public
- Try refreshing after 10 minutes (cache timeout)

### Block not appearing in editor
- Make sure the plugin is activated
- Clear browser cache and refresh
- Check browser console for JavaScript errors

### Styles not loading
- Verify `build/index.css` and `build/style-index.css` exist
- Run `npm run build` if building from source
- Clear WordPress cache if using a caching plugin

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Last.fm account with API key

## Credits

- Design inspired by [Rich Tabor's Cards Block](https://github.com/richtabor/cards-block)
- Built with [@wordpress/scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)
- Uses [Last.fm API](https://www.last.fm/api)

## Support

- Last.fm API Documentation: https://www.last.fm/api/intro
- WordPress Block Editor Handbook: https://developer.wordpress.org/block-editor/

## License

GPL v2 or later

## Author

Serhii - https://serhii.blog

## Version

1.0.0