import React, { useEffect } from 'react';
import { X } from '@phosphor-icons/react';

interface ModalProps {
	isOpen: boolean;
	onClose: () => void;
	onConfirm: () => void;
	title: string;
	message?: string;
	children?: React.ReactNode;
	confirmText?: string;
	cancelText?: string;
	confirmButtonClass?: string;
	disabled?: boolean;
}

export const Modal: React.FC<ModalProps> = ( {
	isOpen,
	onClose,
	onConfirm,
	title,
	message,
	children,
	confirmText = 'Confirm',
	cancelText = 'Cancel',
	confirmButtonClass = 'logmate-btn-danger',
	disabled = false,
} ) => {
	useEffect( () => {
		if ( isOpen ) {
			document.body.style.overflow = 'hidden';
		} else {
			document.body.style.overflow = '';
		}

		return () => {
			document.body.style.overflow = '';
		};
	}, [ isOpen ] );

	useEffect( () => {
		const handleEscape = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' && isOpen ) {
				onClose();
			}
		};

		document.addEventListener( 'keydown', handleEscape );
		return () => {
			document.removeEventListener( 'keydown', handleEscape );
		};
	}, [ isOpen, onClose ] );

	if ( ! isOpen ) {
		return null;
	}

	return (
		<div className="logmate-modal-overlay" onClick={ onClose }>
			<div className="logmate-modal" onClick={ ( e ) => e.stopPropagation() }>
				<div className="logmate-modal-header">
					<h2>{ title }</h2>
					<button className="logmate-modal-close" onClick={ onClose }>
						<X size={ 20 } />
					</button>
				</div>
				<div className="logmate-modal-body">
					{ message && <p>{ message }</p> }
					{ children }
				</div>
				<div className="logmate-modal-footer">
					<button
						className="logmate-btn logmate-btn-secondary"
						onClick={ onClose }
						disabled={ disabled }
					>
						{ cancelText }
					</button>
					<button
						className={ `logmate-btn ${ confirmButtonClass }` }
						onClick={ onConfirm }
						disabled={ disabled }
					>
						{ confirmText }
					</button>
				</div>
			</div>
		</div>
	);
};

