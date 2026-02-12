import React from 'react';

interface ToggleProps {
	checked: boolean;
	onChange: () => void;
	disabled?: boolean;
}

export const Toggle: React.FC< ToggleProps > = ( { checked, onChange, disabled } ) => {
	return (
		<label className="logmate-toggle">
			<input type="checkbox" checked={ checked } onChange={ onChange } disabled={ disabled } />
			<span className="toggle-slider"></span>
		</label>
	);
};

