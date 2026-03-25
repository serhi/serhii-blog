import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, Spinner, Placeholder } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

registerBlockType('lastfm-albums/recent-albums', {
    title: 'Last.fm Recent Albums',
    description: 'Display your recent albums from Last.fm',
    category: 'widgets',
    icon: 'format-audio',
    attributes: {
        albumCount: {
            type: 'number',
            default: 4
        }
    },
    
    edit: ({ attributes, setAttributes }) => {
        const { albumCount } = attributes;
        const [albums, setAlbums] = useState([]);
        const [loading, setLoading] = useState(true);
        const [error, setError] = useState(null);
        
        const blockProps = useBlockProps({
            className: 'lastfm-albums-block-editor'
        });
        
        useEffect(() => {
            setLoading(true);
            setError(null);
            
            apiFetch({ path: `/lastfm-albums/v1/albums?limit=${albumCount}` })
                .then((data) => {
                    setAlbums(data);
                    setLoading(false);
                })
                .catch((err) => {
                    setError(err.message || 'Failed to fetch albums');
                    setLoading(false);
                });
        }, [albumCount]);
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title="Album Settings">
                        <RangeControl
                            label="Number of Albums"
                            value={albumCount}
                            onChange={(value) => setAttributes({ albumCount: value })}
                            min={1}
                            max={12}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div {...blockProps}>
                    {loading && (
                        <Placeholder icon="format-audio" label="Last.fm Albums">
                            <Spinner />
                        </Placeholder>
                    )}
                    
                    {error && (
                        <Placeholder icon="format-audio" label="Last.fm Albums">
                            <div className="lastfm-error">
                                {error}
                                <br />
                                <small>Configure your API key in Settings → Last.fm Albums</small>
                            </div>
                        </Placeholder>
                    )}
                    
                    {!loading && !error && albums.length === 0 && (
                        <Placeholder icon="format-audio" label="Last.fm Albums">
                            <div>No albums found</div>
                        </Placeholder>
                    )}
                    
                    {!loading && !error && albums.length > 0 && (
                        <div className="lastfm-albums-grid alignwide wp-block-tabor-cards is-layout-grid">
                            {albums.map((album, index) => (
                                <div key={index} className="lastfm-album-card wp-block-tabor-card">
                                    {album.image && (
                                        <span className="wp-block-tabor-card__image">
                                            <img src={album.image} alt={`${album.name} by ${album.artist}`} />
                                            <img src={album.image} className="wp-block-tabor-card__image-blur" alt="" />
                                        </span>
                                    )}
                                    <span className={`wp-block-tabor-card__content${album.image ? ' has-image' : ''}`}>
                                        <span className="wp-block-tabor-card__title">{album.name}</span>
                                        <span className="wp-block-tabor-card__description">{album.artist}</span>
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </>
        );
    },
    
    save: () => {
        // Dynamic block - rendered on server
        return null;
    }
});