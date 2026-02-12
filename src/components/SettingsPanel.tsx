import React from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../axios/api';
import { Settings } from '../types';
import { toast } from 'react-toastify';
import { LogPurgeSettings } from './LogPurgeSettings';
import { StatusIndicator } from './StatusIndicator';
import { Toggle } from './Toggle';
import { Spinner } from './Spinner';
import { Tooltip } from './Tooltip';

export const SettingsPanel: React.FC = () => {
	const queryClient = useQueryClient();
	const [ updatingSetting, setUpdatingSetting ] = React.useState< string | null >( null );

	const { data: settings, isLoading } = useQuery< { data: Settings } >( {
		queryKey: [ 'settings' ],
		queryFn: async () => {
			const response = await api.get( '/settings' );
			return response.data;
		},
	} );

	const toggleLoggingMutation = useMutation( {
		mutationFn: async () => {
			const response = await api.post( '/settings/toggle-logging' );
			return response.data;
		},
		onSuccess: ( data ) => {
			toast.success( data.message );
			// Optimistically update cache so UI never reads undefined during refetch.
			const cached = queryClient.getQueryData< { data: Settings } >( [ 'settings' ] );
			if ( cached?.data ) {
				const newStatus = cached.data.log_status === 'enabled' ? 'disabled' : 'enabled';
				queryClient.setQueryData< { data: Settings } >( [ 'settings' ], {
					data: { ...cached.data, log_status: newStatus },
				} );
			}
			queryClient.invalidateQueries( { queryKey: [ 'settings' ] } );
		},
		onError: () => {
			toast.error( 'Failed to toggle logging' );
		},
	} );

	const updateSettingsMutation = useMutation( {
		mutationFn: async ( updatedSettings: Partial< Settings > ) => {
			const response = await api.post( '/settings/update', updatedSettings );
			return response.data;
		},
		onSuccess: ( data, variables ) => {
			toast.success( data.message );
			setUpdatingSetting( null );
			// Optimistically update the settings in the cache immediately for instant UI updates.
			const cached = queryClient.getQueryData< { data: Settings } >( [ 'settings' ] );
			if ( cached?.data ) {
				queryClient.setQueryData< { data: Settings } >( [ 'settings' ], {
					data: {
						...cached.data,
						...variables,
					},
				} );
			}
			// Refetch in the background to ensure we have the latest from server.
			queryClient.refetchQueries( { queryKey: [ 'settings' ] } );
		},
		onError: () => {
			toast.error( 'Failed to update settings' );
			setUpdatingSetting( null );
		},
	} );

	const handleSettingUpdate = ( settingKey: keyof Settings, currentValue: string ) => {
		// Prevent multiple clicks if this specific setting is already updating
		if ( updatingSetting === settingKey || updateSettingsMutation.isPending || toggleLoggingMutation.isPending ) {
			return;
		}
		setUpdatingSetting( settingKey );
		updateSettingsMutation.mutate( {
			[ settingKey ]: currentValue === 'enabled' ? 'disabled' : 'enabled',
		} );
	};

	// Guard against undefined settings or settings.data (e.g. after invalidateQueries during refetch).
	if ( isLoading || ! settings?.data ) {
		return (
			<div className="logmate-screen">
				<Spinner />
			</div>
		);
	}

	const currentSettings = settings.data;
	const isLoggingEnabled = currentSettings.log_status === 'enabled';

	return (
		<div className="logmate-screen">
			<div className="logmate-settings">
				<div className="logmate-settings-section">
					<div className="logmate-setting-header">
						<StatusIndicator
							status={ currentSettings.log_status }
							onToggle={ () => toggleLoggingMutation.mutate() }
							loading={ toggleLoggingMutation.isPending }
						/>
					</div>
				</div>

				{ isLoggingEnabled && (
					<>
						<div className="logmate-settings-section">
							<div className="logmate-setting-item">
							<div className="logmate-setting-row">
								<div className="logmate-setting-label-wrapper">
									<label>Auto-refresh logs</label>
									<Tooltip 
										content="Automatically refresh logs every 10 seconds to show new entries in real-time."
										position="right"
									/>
								</div>
								<div className="logmate-toggle-wrapper">
									<Toggle
										checked={ currentSettings.autorefresh === 'enabled' }
										onChange={ () => handleSettingUpdate( 'autorefresh', currentSettings.autorefresh ) }
										disabled={ updatingSetting === 'autorefresh' || toggleLoggingMutation.isPending }
									/>
									{ updatingSetting === 'autorefresh' && (
										<div className="logmate-toggle-loader">
											<Spinner />
										</div>
									) }
								</div>
							</div>
						</div>
						<div className="logmate-setting-item">
							<div className="logmate-setting-row">
								<div className="logmate-setting-label-wrapper">
									<label>Log JavaScript errors</label>
									<Tooltip 
										content="Capture and log JavaScript errors from the frontend to a separate log file."
										position="right"
									/>
								</div>
								<div className="logmate-toggle-wrapper">
									<Toggle
										checked={ currentSettings.js_error_logging === 'enabled' }
										onChange={ () => handleSettingUpdate( 'js_error_logging', currentSettings.js_error_logging ) }
										disabled={ updatingSetting === 'js_error_logging' || toggleLoggingMutation.isPending }
									/>
									{ updatingSetting === 'js_error_logging' && (
										<div className="logmate-toggle-loader">
											<Spinner />
										</div>
									) }
								</div>
							</div>
						</div>
						<div className="logmate-setting-item">
							<div className="logmate-setting-row">
								<div className="logmate-setting-label-wrapper">
									<label>Modify SCRIPT_DEBUG</label>
									<Tooltip 
										content="Controls SCRIPT_DEBUG in wp-config.php. Enables non-minified JS/CSS for easier debugging."
										position="right"
									/>
								</div>
								<div className="logmate-toggle-wrapper">
									<Toggle
										checked={ currentSettings.modify_script_debug === 'enabled' }
										onChange={ () => handleSettingUpdate( 'modify_script_debug', currentSettings.modify_script_debug ) }
										disabled={ updatingSetting === 'modify_script_debug' || toggleLoggingMutation.isPending }
									/>
									{ updatingSetting === 'modify_script_debug' && (
										<div className="logmate-toggle-loader">
											<Spinner />
										</div>
									) }
								</div>
							</div>
						</div>
						</div>

						<LogPurgeSettings />
					</>
				) }
			</div>
		</div>
	);
};

