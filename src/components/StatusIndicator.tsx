import React from 'react';
import { Toggle } from './Toggle';
import { Spinner } from './Spinner';
import { Tooltip } from './Tooltip';

interface StatusIndicatorProps {
	status: string;
	onToggle: () => void;
	loading?: boolean;
}

export const StatusIndicator: React.FC< StatusIndicatorProps > = ( { status, onToggle, loading } ) => {
	const isEnabled = status === 'enabled';

	return (
		<div className="logmate-setting-row">
			<div className="status-info">
				<div className="logmate-setting-label-wrapper">
					<span className={ `status-badge ${ isEnabled ? 'enabled' : 'disabled' }` }>
						{ isEnabled ? 'Enabled' : 'Disabled' }
					</span>
					<Tooltip 
						content="Enable/disable WordPress debug logging. Updates WP_DEBUG constants in wp-config.php."
						position="right"
					/>
				</div>
			</div>
			<div className="logmate-toggle-wrapper">
				<Toggle checked={ isEnabled } onChange={ onToggle } disabled={ loading } />
				{ loading && (
					<div className="logmate-toggle-loader">
						<Spinner />
					</div>
				) }
			</div>
		</div>
	);
};

