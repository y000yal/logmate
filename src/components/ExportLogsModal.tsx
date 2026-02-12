import React, { useState, useEffect } from 'react';
import { X, Download } from '@phosphor-icons/react';
import api from '../axios/api';
import { toast } from 'react-toastify';

interface ExportLogsModalProps {
	isOpen: boolean;
	onClose: () => void;
	logType: 'all' | 'php' | 'js';
}

export const ExportLogsModal: React.FC<ExportLogsModalProps> = ({
	isOpen,
	onClose,
	logType,
}) => {
	const [exportType, setExportType] = useState<'date-range' | 'entire-file'>('date-range');
	const [startDate, setStartDate] = useState('');
	const [endDate, setEndDate] = useState('');
	const [isExporting, setIsExporting] = useState(false);

	useEffect(() => {
		if (isOpen) {
			document.body.style.overflow = 'hidden';
		} else {
			document.body.style.overflow = '';
		}

		return () => {
			document.body.style.overflow = '';
		};
	}, [isOpen]);

	useEffect(() => {
		const handleEscape = (e: KeyboardEvent) => {
			if (e.key === 'Escape' && isOpen) {
				onClose();
			}
		};

		document.addEventListener('keydown', handleEscape);
		return () => {
			document.removeEventListener('keydown', handleEscape);
		};
	}, [isOpen, onClose]);

	const handleExport = async () => {
		if (exportType === 'date-range' && (!startDate || !endDate)) {
			toast.error('Please select both start and end dates');
			return;
		}

		if (exportType === 'date-range' && new Date(startDate) > new Date(endDate)) {
			toast.error('Start date must be before end date');
			return;
		}

		setIsExporting(true);

		try {
			const params: any = {
				type: logType,
				export_type: exportType,
			};

			if (exportType === 'date-range') {
				params.start_date = startDate;
				params.end_date = endDate;
			}

			const response = await api.get('/logs/export', {
				params,
				responseType: 'blob',
			});

			// Create a blob URL and trigger download
			const blob = new Blob([response.data], { type: 'text/plain' });
			const url = window.URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			
			const logTypeText = logType === 'all' ? 'all' : logType === 'php' ? 'php' : 'js';
			const dateSuffix = exportType === 'date-range' 
				? `_${startDate}_to_${endDate}` 
				: '_entire';
			link.download = `debug-logs-${logTypeText}${dateSuffix}.txt`;
			
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
			window.URL.revokeObjectURL(url);

			toast.success('Logs exported successfully');
			onClose();
		} catch (error: any) {
			toast.error(error.response?.data?.message || 'Failed to export logs');
		} finally {
			setIsExporting(false);
		}
	};

	if (!isOpen) {
		return null;
	}

	return (
		<div className="logmate-modal-overlay" onClick={onClose}>
			<div className="logmate-modal" onClick={(e) => e.stopPropagation()}>
				<div className="logmate-modal-header">
					<h2>Export Logs</h2>
					<button className="logmate-modal-close" onClick={onClose}>
						<X size={20} />
					</button>
				</div>
				<div className="logmate-modal-body">
					<div className="logmate-export-options">
						<div className="logmate-radio-group">
							<label className="logmate-radio-label">
								<input
									type="radio"
									name="exportType"
									value="date-range"
									checked={exportType === 'date-range'}
									onChange={(e) => setExportType(e.target.value as 'date-range')}
									className="logmate-radio"
								/>
								<span>Export by Date Range</span>
							</label>
							{exportType === 'date-range' && (
								<div className="logmate-date-range-inputs">
									<div className="logmate-date-input-group">
										<label>Start Date</label>
										<input
											type="date"
											value={startDate}
											onChange={(e) => setStartDate(e.target.value)}
											className="logmate-input logmate-datetime-input"
										/>
									</div>
									<div className="logmate-date-input-group">
										<label>End Date</label>
										<input
											type="date"
											value={endDate}
											onChange={(e) => setEndDate(e.target.value)}
											className="logmate-input logmate-datetime-input"
										/>
									</div>
								</div>
							)}
						</div>
						<div className="logmate-radio-group">
							<label className="logmate-radio-label">
								<input
									type="radio"
									name="exportType"
									value="entire-file"
									checked={exportType === 'entire-file'}
									onChange={(e) => setExportType(e.target.value as 'entire-file')}
									className="logmate-radio"
								/>
								<span>Export Entire File</span>
							</label>
						</div>
					</div>
				</div>
				<div className="logmate-modal-footer">
					<button
						className="logmate-btn logmate-btn-secondary"
						onClick={onClose}
						disabled={isExporting}
					>
						Cancel
					</button>
					<button
						className="logmate-btn logmate-btn-primary"
						onClick={handleExport}
						disabled={isExporting}
					>
						<Download size={18} />
						{isExporting ? 'Exporting...' : 'Export Logs'}
					</button>
				</div>
			</div>
		</div>
	);
};

