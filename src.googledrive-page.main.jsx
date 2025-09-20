import { createRoot, render, StrictMode, useState, useEffect, createInterpolateElement } from '@wordpress/element';
import { Button, TextControl, Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

import "./scss/style.scss"

const domElement = document.getElementById( window.wpmudevDriveTest.dom_element_id );

const WPMUDEV_DriveTest = () => {
    const [isAuthenticated, setIsAuthenticated] = useState(window.wpmudevDriveTest.authStatus || false);
    const [hasCredentials, setHasCredentials] = useState(window.wpmudevDriveTest.hasCredentials || false);
    const [showCredentials, setShowCredentials] = useState(!window.wpmudevDriveTest.hasCredentials);
    const [isLoading, setIsLoading] = useState(false);
    const [files, setFiles] = useState([]);
    const [uploadFile, setUploadFile] = useState(null);
    const [folderName, setFolderName] = useState('');
    const [notice, setNotice] = useState({ message: '', type: '' });
    const [credentials, setCredentials] = useState({
        clientId: window.wpmudevDriveTest.clientId || '',
        clientSecret: window.wpmudevDriveTest.clientSecret || ''
    });

    useEffect(() => {
        if (isAuthenticated) {
            loadFiles();
        }
    }, [isAuthenticated]);

    const showNotice = (message, type = 'success') => {
        setNotice({ message, type });
        setTimeout(() => setNotice({ message: '', type: '' }), 5000);
    };

    const handleSaveCredentials = async () => {
        setIsLoading(true);
        try {
            const response = await apiFetch({
                path: 'wp-json/wpmudev/v1/drive/save-credentials',
                method: 'POST',
                data: {
                    client_id: credentials.clientId,
                    client_secret: credentials.clientSecret
                }
            });

            if (response.success) {
                setHasCredentials(true);
                setShowCredentials(false);
                showNotice('Credentials saved successfully!', 'success');
            } else {
                showNotice(response.message || 'Failed to save credentials', 'error');
            }
        } catch (error) {
            showNotice('Error saving credentials: ' + error.message, 'error');
        }
        setIsLoading(false);
    };

    const handleAuth = async () => {
        setIsLoading(true);
        try {
            const response = await apiFetch({
                path: 'wp-json/wpmudev/v1/drive/init-auth',
                method: 'POST'
            });

            if (response.success && response.data.auth_url) {
                window.location.href = response.data.auth_url;
            } else {
                showNotice('Failed to initialize authentication', 'error');
            }
        } catch (error) {
            showNotice('Error initializing authentication: ' + error.message, 'error');
        }
        setIsLoading(false);
    };

    const loadFiles = async () => {
        setIsLoading(true);
        try {
            const response = await apiFetch({
                path: 'wp-json/wpmudev/v1/drive/files',
                method: 'GET'
            });

            if (response.success) {
                setFiles(response.data.files || []);
                showNotice('Files loaded successfully', 'success');
            } else {
                showNotice(response.message || 'Failed to load files', 'error');
            }
        } catch (error) {
            showNotice('Error loading files: ' + error.message, 'error');
        }
        setIsLoading(false);
    };

    const handleUpload = async () => {
        if (!uploadFile) return;
        
        setIsLoading(true);
        try {
            const formData = new FormData();
            formData.append('file', uploadFile);
            formData.append('action', 'wpmudev_drive_test_upload');

            const response = await fetch(window.wpmudevDriveTest.ajax_url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-WP-Nonce': window.wpmudevDriveTest.nonce
                }
            });

            const data = await response.json();

            if (data.success) {
                setUploadFile(null);
                showNotice('File uploaded successfully!', 'success');
                loadFiles();
            } else {
                showNotice(data.data || 'Failed to upload file', 'error');
            }
        } catch (error) {
            showNotice('Error uploading file: ' + error.message, 'error');
        }
        setIsLoading(false);
    };

    const handleDownload = async (fileId, fileName) => {
        setIsLoading(true);
        try {
            const response = await apiFetch({
                path: 'wp-json/wpmudev/v1/drive/download',
                method: 'POST',
                data: { file_id: fileId, file_name: fileName }
            });

            if (response.success && response.data.download_url) {
                window.open(response.data.download_url, '_blank');
                showNotice('Download initiated', 'success');
            } else {
                showNotice(response.message || 'Failed to download file', 'error');
            }
        } catch (error) {
            showNotice('Error downloading file: ' + error.message, 'error');
        }
        setIsLoading(false);
    };

    const handleCreateFolder = async () => {
        if (!folderName.trim()) return;
        
        setIsLoading(true);
        try {
            const response = await apiFetch({
                path: 'wp-json/wpmudev/v1/drive/create-folder',
                method: 'POST',
                data: { folder_name: folderName.trim() }
            });

            if (response.success) {
                setFolderName('');
                showNotice('Folder created successfully!', 'success');
                loadFiles();
            } else {
                showNotice(response.message || 'Failed to create folder', 'error');
            }
        } catch (error) {
            showNotice('Error creating folder: ' + error.message, 'error');
        }
        setIsLoading(false);
    };

    return (
        <>
            <div className="sui-header">
                <h1 className="sui-header-title">
                    Google Drive Test
                </h1>
                <p className="sui-description">Test Google Drive API integration for applicant assessment</p>
            </div>

            {notice.message && (
                <Notice status={notice.type} isDismissible onRemove={() => setNotice({ message: '', type: '' })}>
                    {notice.message}
                </Notice>
            )}

            {showCredentials ? (
                <div className="sui-box">
                    <div className="sui-box-header">
                        <h2 className="sui-box-title">Set Google Drive Credentials</h2>
                    </div>
                    <div className="sui-box-body">
                        <div className="sui-box-settings-row">
                            <TextControl
                                help={createInterpolateElement(
                                    'You can get Client ID from <a>Google Cloud Console</a>. Make sure to enable Google Drive API.',
                                    {
                                        a: <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer" />,
                                    }
                                )}
                                label="Client ID"
                                value={credentials.clientId}
                                onChange={(value) => setCredentials({...credentials, clientId: value})}
                            />
                        </div>

                        <div className="sui-box-settings-row">
                            <TextControl
                                help={createInterpolateElement(
                                    'You can get Client Secret from <a>Google Cloud Console</a>.',
                                    {
                                        a: <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer" />,
                                    }
                                )}
                                label="Client Secret"
                                value={credentials.clientSecret}
                                onChange={(value) => setCredentials({...credentials, clientSecret: value})}
                                type="password"
                            />
                        </div>

                        <div className="sui-box-settings-row">
                            <span>Please use this URL <em>{window.wpmudevDriveTest.redirectUri}</em> in your Google API's <strong>Authorized redirect URIs</strong> field.</span>
                        </div>

                        <div className="sui-box-settings-row">
                            <p><strong>Required scopes for Google Drive API:</strong></p>
                            <ul>
                                <li>https://www.googleapis.com/auth/drive.file</li>
                                <li>https://www.googleapis.com/auth/drive.readonly</li>
                            </ul>
                        </div>
                    </div>
                    <div className="sui-box-footer">
                        <div className="sui-actions-right">
                            <Button
                                variant="primary"
                                onClick={handleSaveCredentials}
                                disabled={isLoading || !credentials.clientId || !credentials.clientSecret}
                            >
                                {isLoading ? <Spinner /> : 'Save Credentials'}
                            </Button>
                        </div>
                    </div>
                </div>
            ) : !isAuthenticated ? (
                <div className="sui-box">
                    <div className="sui-box-header">
                        <h2 className="sui-box-title">Authenticate with Google Drive</h2>
                    </div>
                    <div className="sui-box-body">
                        <div className="sui-box-settings-row">
                            <p>Please authenticate with Google Drive to proceed with the test.</p>
                            <p><strong>This test will require the following permissions:</strong></p>
                            <ul>
                                <li>View and manage Google Drive files</li>
                                <li>Upload new files to Drive</li>
                                <li>Create folders in Drive</li>
                            </ul>
                        </div>
                    </div>
                    <div className="sui-box-footer">
                        <div className="sui-actions-left">
                            <Button
                                variant="secondary"
                                onClick={() => setShowCredentials(true)}
                            >
                                Change Credentials
                            </Button>
                        </div>
                        <div className="sui-actions-right">
                            <Button
                                variant="primary"
                                onClick={handleAuth}
                                disabled={isLoading}
                            >
                                {isLoading ? <Spinner /> : 'Authenticate with Google Drive'}
                            </Button>
                        </div>
                    </div>
                </div>
            ) : (
                <>
                    {/* File Upload Section */}
                    <div className="sui-box">
                        <div className="sui-box-header">
                            <h2 className="sui-box-title">Upload File to Drive</h2>
                        </div>
                        <div className="sui-box-body">
                            <div className="sui-box-settings-row">
                                <input
                                    type="file"
                                    onChange={(e) => setUploadFile(e.target.files[0])}
                                    className="drive-file-input"
                                />
                                {uploadFile && (
                                    <p><strong>Selected:</strong> {uploadFile.name} ({Math.round(uploadFile.size / 1024)} KB)</p>
                                )}
                            </div>
                        </div>
                        <div className="sui-box-footer">
                            <div className="sui-actions-right">
                                <Button
                                    variant="primary"
                                    onClick={handleUpload}
                                    disabled={isLoading || !uploadFile}
                                >
                                    {isLoading ? <Spinner /> : 'Upload to Drive'}
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Create Folder Section */}
                    <div className="sui-box">
                        <div className="sui-box-header">
                            <h2 className="sui-box-title">Create New Folder</h2>
                        </div>
                        <div className="sui-box-body">
                            <div className="sui-box-settings-row">
                                <TextControl
                                    label="Folder Name"
                                    value={folderName}
                                    onChange={setFolderName}
                                    placeholder="Enter folder name"
                                />
                            </div>
                        </div>
                        <div className="sui-box-footer">
                            <div className="sui-actions-right">
                                <Button
                                    variant="secondary"
                                    onClick={handleCreateFolder}
                                    disabled={isLoading || !folderName.trim()}
                                >
                                    {isLoading ? <Spinner /> : 'Create Folder'}
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Files List Section */}
                    <div className="sui-box">
                        <div className="sui-box-header">
                            <h2 className="sui-box-title">Your Drive Files</h2>
                            <div className="sui-actions-right">
                                <Button
                                    variant="secondary"
                                    onClick={loadFiles}
                                    disabled={isLoading}
                                >
                                    {isLoading ? <Spinner /> : 'Refresh Files'}
                                </Button>
                            </div>
                        </div>
                        <div className="sui-box-body">
                            {isLoading ? (
                                <div className="drive-loading">
                                    <Spinner />
                                    <p>Loading files...</p>
                                </div>
                            ) : files.length > 0 ? (
                                <div className="drive-files-grid">
                                    {files.map((file) => (
                                        <div key={file.id} className="drive-file-item">
                                            <div className="file-info">
                                                <strong>{file.name}</strong>
                                                <small>
                                                    {file.modifiedTime ? new Date(file.modifiedTime).toLocaleDateString() : 'Unknown date'}
                                                </small>
                                            </div>
                                            <div className="file-actions">
                                                {file.webViewLink && (
                                                    <Button
                                                        variant="link"
                                                        size="small"
                                                        href={file.webViewLink}
                                                        target="_blank"
                                                    >
                                                        View in Drive
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="link"
                                                    size="small"
                                                    onClick={() => handleDownload(file.id, file.name)}
                                                >
                                                    Download
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="sui-box-settings-row">
                                    <p>No files found in your Drive. Upload a file or create a folder to get started.</p>
                                </div>
                            )}
                        </div>
                    </div>
                </>
            )}
        </>
    );
}

if ( createRoot ) {
    createRoot( domElement ).render(<StrictMode><WPMUDEV_DriveTest/></StrictMode>);
} else {
    render( <StrictMode><WPMUDEV_DriveTest/></StrictMode>, domElement );
}