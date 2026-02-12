import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import { ToastContainer } from 'react-toastify';
import { Tooltip as ReactTooltip } from 'react-tooltip';

import './styles/admin.css';
import 'react-tooltip/dist/react-tooltip.css';
import { Router } from './components/router/Router';
import { IconInjector } from './components/IconInjector';

const queryClient = new QueryClient( {
	defaultOptions: {
		queries: {
			refetchOnWindowFocus: false,
			retry: 1,
		},
	},
} );

export const App: React.FC = () => {
	return (
		<QueryClientProvider client={ queryClient }>
			<HashRouter>
				<IconInjector />
				<Router />
				<ToastContainer
					position="top-right"
					autoClose={ 3000 }
					hideProgressBar={ false }
					newestOnTop
					closeOnClick
					pauseOnFocusLoss
					draggable
					pauseOnHover
				/>
				<ReactTooltip id="logmate-tooltip" />
			</HashRouter>
			<ReactQueryDevtools initialIsOpen={ false } />
		</QueryClientProvider>
	);
};

