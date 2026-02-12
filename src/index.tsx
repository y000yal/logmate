import React from 'react';
import { createRoot, Root } from 'react-dom/client';
import { App } from './App';

// Type declaration for webpack HMR
declare const module: {
	hot?: {
		accept: ( module: string | (() => void), callback?: () => void ) => void;
	};
};

let root: Root | null = null;

const render = () => {
	const container = document.getElementById( 'logmate-admin-app' );
	if ( container ) {
		if ( ! root ) {
			root = createRoot( container );
		}
		root.render( <App /> );
	}
};

// Initial render
render();

// Manual HMR - re-render when App or any of its dependencies change
if ( module.hot ) {
	// Accept the App module and all its dependencies
	module.hot.accept( './App', () => {
		// Re-render the app when App or any child component changes
		render();
	} );
}

