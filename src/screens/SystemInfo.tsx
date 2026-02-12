import React from 'react';
import {useQuery} from '@tanstack/react-query';
import api from '../axios/api';
import {Spinner} from '../components/Spinner';

interface SystemInfo {
    php: {
        version: string;
        memory_limit: string;
        max_execution_time: string;
        upload_max_filesize: string;
        post_max_size: string;
        max_input_vars: string;
    };
    wordpress: {
        version: string;
        memory_limit: string;
        max_memory_limit: string;
    };
    server: {
        software: string;
        os: string;
        db_version: string;
        db_charset: string;
        db_collate: string;
        db_extension: string;
    };
    plugins: Array<{
        name: string;
        version: string;
        active: boolean;
    }>;
    theme: {
        name: string;
        version: string;
        author: string;
    };
    debug_logs: {
        php_log_size: string;
        js_log_size: string;
        total_size: string;
        php_log_path: string;
        js_log_path: string;
        php_log_exists: boolean;
        js_log_exists: boolean;
    };
}

export const SystemInfoScreen: React.FC = () => {
    const {data, isLoading, error} = useQuery<{ data: SystemInfo }>({
        queryKey: ['system-info'],
        queryFn: async () => {
            const response = await api.get('/system-info');
            return response.data;
        },
    });

    if (isLoading) {
        return (
            <div className="logmate-screen">
                <Spinner/>
            </div>
        );
    }

    if (error) {
        return (
            <div className="logmate-screen">
                <div className="logmate-error">
                    Error loading system information. Please try again.
                </div>
            </div>
        );
    }

    const systemInfo = data?.data;

    if (!systemInfo) {
        return null;
    }

    return (
        <div className="logmate-screen">
            <div className="logmate-system-info-mosaic">

                {/* PHP Card */}
                <div className="logmate-mosaic-card logmate-card-large">
                    <div className="logmate-card-header">
                        <h2>PHP Configurations</h2>
                    </div>
                    <div className="logmate-card-content">
                        <div className="logmate-stat-grid">
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">Version</span>
                                <span className="logmate-stat-value">{systemInfo.php.version}</span>
                            </div>
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">Memory Limit</span>
                                <span className="logmate-stat-value">{systemInfo.php.memory_limit}</span>
                            </div>
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">Max Execution</span>
                                <span className="logmate-stat-value">{systemInfo.php.max_execution_time}s</span>
                            </div>
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">Upload Max</span>
                                <span className="logmate-stat-value">{systemInfo.php.upload_max_filesize}</span>
                            </div>
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">Post Max</span>
                                <span className="logmate-stat-value">{systemInfo.php.post_max_size}</span>
                            </div>
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">Max Input Vars</span>
                                <span className="logmate-stat-value">{systemInfo.php.max_input_vars}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* WordPress Card */}
                <div className="logmate-mosaic-card">
                    <div className="logmate-card-header">
                        <h2>WordPress</h2>
                    </div>
                    <div className="logmate-card-content">
                        <div className="logmate-stat-item">
                            <span className="logmate-stat-label">Version</span>
                            <span className="logmate-stat-value">{systemInfo.wordpress.version}</span>
                        </div>
                        <div className="logmate-stat-item">
                            <span className="logmate-stat-label">Memory Limit</span>
                            <span className="logmate-stat-value">{systemInfo.wordpress.memory_limit}</span>
                        </div>
                        <div className="logmate-stat-item">
                            <span className="logmate-stat-label">Max Memory</span>
                            <span className="logmate-stat-value">{systemInfo.wordpress.max_memory_limit}</span>
                        </div>
                    </div>
                </div>

                {/* Theme Card */}
                <div className="logmate-mosaic-card">
                    <div className="logmate-card-header">
                        <h2>Theme</h2>
                    </div>
                    <div className="logmate-card-content">
                        <div className="logmate-stat-item">
                            <span className="logmate-stat-label">Name</span>
                            <span className="logmate-stat-value">{systemInfo.theme.name}</span>
                        </div>
                        <div className="logmate-stat-item">
                            <span className="logmate-stat-label">Version</span>
                            <span className="logmate-stat-value">{systemInfo.theme.version}</span>
                        </div>
                        <div className="logmate-stat-item">
                            <span className="logmate-stat-label">Author</span>
                            <span className="logmate-stat-value">{systemInfo.theme.author}</span>
                        </div>
                    </div>
                </div>
                {/* Debug Logs Card */}
                <div className="logmate-mosaic-card logmate-card-large">
                    <div className="logmate-card-header">
                        <h2>Debug Logs</h2>
                    </div>
                    <div className="logmate-card-content">
                        <div className="logmate-stat-item">
                            <span className="logmate-stat-label">Total Size</span>
                            <span className="logmate-stat-value">{systemInfo.debug_logs.total_size}</span>
                        </div>
                        <div className="logmate-stat-item">
                            <span className="logmate-stat-label">PHP Log</span>
                            <span className="logmate-stat-value">{systemInfo.debug_logs.php_log_size}</span>
                        </div>
                        <div className="logmate-stat-item">
                            <span className="logmate-stat-label">JS Log</span>
                            <span className="logmate-stat-value">{systemInfo.debug_logs.js_log_size}</span>
                        </div>
                        {systemInfo.debug_logs.php_log_exists && (
                            <div className="logmate-stat-item logmate-stat-fullwidth">
                                <span className="logmate-stat-label">PHP Path</span>
                                <span className="logmate-stat-value logmate-stat-path">
                                    {systemInfo.debug_logs.php_log_path}
                                </span>
                            </div>
                        )}
                        {systemInfo.debug_logs.js_log_exists && (
                            <div className="logmate-stat-item logmate-stat-fullwidth">
                                <span className="logmate-stat-label">JS Path</span>
                                <span className="logmate-stat-value logmate-stat-path">
                                    {systemInfo.debug_logs.js_log_path}
                                </span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Server & Database Card */}
                <div className="logmate-mosaic-card logmate-card-large">
                    <div className="logmate-card-header">
                        <h2>Server & Database</h2>
                    </div>
                    <div className="logmate-card-content">
                        <div className="logmate-stat-grid">
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">Softwares</span>
                                <span className="logmate-stat-value">{systemInfo.server.software}</span>
                            </div>
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">OS</span>
                                <span className="logmate-stat-value">{systemInfo.server.os}</span>
                            </div>
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">DB Version</span>
                                <span className="logmate-stat-value">{systemInfo.server.db_version}</span>
                            </div>
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">DB Extension</span>
                                <span className="logmate-stat-value">{systemInfo.server.db_extension}</span>
                            </div>
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">Charset</span>
                                <span className="logmate-stat-value">{systemInfo.server.db_charset}</span>
                            </div>
                            <div className="logmate-stat-item">
                                <span className="logmate-stat-label">Collation</span>
                                <span className="logmate-stat-value">{systemInfo.server.db_collate}</span>
                            </div>
                        </div>
                    </div>
                </div>


                {/* Plugins Card */}
                <div className="logmate-mosaic-card ">
                    <div className="logmate-card-header">
                        <h2>Plugins ({systemInfo.plugins.length})</h2>
                    </div>
                    <div className="logmate-card-content">
                        <div className="logmate-plugins-list">
                            {systemInfo.plugins.map((plugin, index) => (
                                <div
                                    key={index}
                                    className={`logmate-plugin-item ${plugin.active ? 'active' : ''}`}
                                >
                                    <div className="logmate-plugin-name">
                                        {plugin.name}
                                        {plugin.active && (
                                            <span className="logmate-plugin-badge">Active</span>
                                        )}
                                    </div>
                                    <div className="logmate-plugin-version">v{plugin.version}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

