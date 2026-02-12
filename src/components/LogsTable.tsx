import React, { useMemo, useState } from 'react';
import {
	useReactTable,
	getCoreRowModel,
	getFilteredRowModel,
	getPaginationRowModel,
	getSortedRowModel,
	ColumnDef,
	flexRender,
	SortingState,
	ColumnFiltersState,
} from '@tanstack/react-table';
import { LogEntry } from '../types';
import { Trash, ArrowClockwise, Stack, Code, Browser, Download } from '@phosphor-icons/react';
import api from '../axios/api';
import { toast } from 'react-toastify';
import { Modal } from './Modal';
import { TableSkeleton } from './TableSkeleton';
import { ExportLogsModal } from './ExportLogsModal';

interface LogsTableProps {
	entries: LogEntry[];
	onRefresh: () => void;
	logType?: 'all' | 'php' | 'js';
	setLogType?: ( type: 'all' | 'php' | 'js' ) => void;
	isLoading?: boolean;
	isFetching?: boolean;
}

export const LogsTable: React.FC< LogsTableProps > = ( {
	entries,
	onRefresh,
	logType = 'all',
	setLogType,
	isLoading = false,
	isFetching = false,
} ) => {
	const [ sorting, setSorting ] = useState< SortingState >( [] );
	const [ columnFilters, setColumnFilters ] = useState< ColumnFiltersState >( [] );
	const [ globalFilter, setGlobalFilter ] = useState( '' );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ isExportModalOpen, setIsExportModalOpen ] = useState( false );

	const columns = useMemo< ColumnDef< LogEntry >[] >(
		() => [
			{
				accessorKey: 'log_type',
				header: 'Log Type',
				cell: ( info ) => {
					const logType = ( info.getValue() as string ) || 'php'; // Default to PHP for backward compatibility.
					return (
						<span className={ `log-log-type log-log-type-${ logType }` }>
							{ logType === 'php' ? 'PHP' : logType === 'js' ? 'JavaScript' : 'Unknown' }
						</span>
					);
				},
			},
			{
				accessorKey: 'type',
				header: 'Error Type',
				cell: ( info ) => (
					<span className={ `log-type log-type-${ info.getValue().toString().toLowerCase().replace( ' ', '-' ) }` }>
						{ info.getValue() as string }
					</span>
				),
			},
			{
				accessorKey: 'message',
				header: 'Message',
				cell: ( info ) => (
					<div className="log-message" dangerouslySetInnerHTML={ { __html: info.getValue() as string } } />
				),
			},
			{
				accessorKey: 'source',
				header: 'Source',
			},
			{
				accessorKey: 'count',
				header: 'Occurrences',
				cell: ( info ) => <span className="log-count">{ info.getValue() as number }</span>,
			},
			{
				accessorKey: 'occurrences',
				header: 'Last Occurrence',
				cell: ( info ) => {
					const occurrences = info.getValue() as string[];
					const latest = occurrences[ occurrences.length - 1 ];
					return <span className="log-timestamp">{ latest }</span>;
				},
			},
		],
		[]
	);

	const table = useReactTable( {
		data: entries,
		columns,
		getCoreRowModel: getCoreRowModel(),
		getFilteredRowModel: getFilteredRowModel(),
		getPaginationRowModel: getPaginationRowModel(),
		getSortedRowModel: getSortedRowModel(),
		onSortingChange: setSorting,
		onColumnFiltersChange: setColumnFilters,
		onGlobalFilterChange: setGlobalFilter,
		state: {
			sorting,
			columnFilters,
			globalFilter,
		},
		initialState: {
			pagination: {
				pageSize: 50,
			},
		},
	} );

	const handleClearLogs = async () => {
		const logTypeText = logType === 'all' ? 'all logs' : logType === 'php' ? 'PHP logs' : 'JavaScript logs';
		
		try {
			await api.post( '/logs/clear', null, {
				params: { type: logType },
			} );
			toast.success( `${ logTypeText.charAt( 0 ).toUpperCase() + logTypeText.slice( 1 ) } cleared successfully` );
			setIsModalOpen( false );
			onRefresh();
		} catch ( error ) {
			toast.error( 'Failed to clear logs' );
			setIsModalOpen( false );
		}
	};

	const openClearModal = () => {
		setIsModalOpen( true );
	};

	const logTypeText = logType === 'all' ? 'all logs' : logType === 'php' ? 'PHP logs' : 'JavaScript logs';

	return (
		<>
			<div className="logmate-table-container">
				<div className="logmate-table-toolbar">
					<div className="logmate-table-toolbar-left">
						{ setLogType && (
							<div className="logmate-log-filters">
								<button
									className={ `logmate-filter-btn ${ logType === 'all' ? 'active' : '' }` }
									onClick={ () => setLogType( 'all' ) }
									title="All Logs"
								>
									<Stack size={ 18 } />
									<span className="logmate-filter-btn-text">All</span>
								</button>
								<button
									className={ `logmate-filter-btn ${ logType === 'php' ? 'active' : '' }` }
									onClick={ () => setLogType( 'php' ) }
									title="PHP Logs"
								>
									<Code size={ 18 } />
									<span className="logmate-filter-btn-text">PHP</span>
								</button>
								<button
									className={ `logmate-filter-btn ${ logType === 'js' ? 'active' : '' }` }
									onClick={ () => setLogType( 'js' ) }
									title="JavaScript Logs"
								>
									<Browser size={ 18 } />
									<span className="logmate-filter-btn-text">JS</span>
								</button>
							</div>
						) }
						<input
							type="text"
							placeholder="Search logs..."
							value={ globalFilter ?? '' }
							onChange={ ( e ) => setGlobalFilter( e.target.value ) }
							className="logmate-search"
						/>
						<button
							onClick={ () => setIsExportModalOpen( true ) }
							className="logmate-btn logmate-btn-secondary logmate-export-btn"
							title="Export Logs"
						>
							<Download size={ 18 } />
							Export Logs
						</button>
					</div>
					<div className="logmate-table-actions">
						<button
							onClick={ onRefresh }
							className="logmate-btn logmate-btn-secondary"
							disabled={ isFetching }
						>
							<ArrowClockwise
								size={ 18 }
								className={ isFetching ? 'logmate-refresh-icon-rotating' : '' }
							/>
							Refresh
						</button>
						<button
							onClick={ openClearModal }
							className="logmate-btn logmate-btn-danger"
						>
							<Trash size={ 18 } />
							Clear Logs
						</button>
					</div>
				</div>

				<table className="logmate-table">
					<thead>
						{ table.getHeaderGroups().map( ( headerGroup ) => (
							<tr key={ headerGroup.id }>
								{ headerGroup.headers.map( ( header ) => (
									<th
										key={ header.id }
										onClick={ header.column.getToggleSortingHandler() }
										className={ header.column.getCanSort() ? 'sortable' : '' }
									>
										{ flexRender( header.column.columnDef.header, header.getContext() ) }
										{ {
											asc: ' ↑',
											desc: ' ↓',
										}[ header.column.getIsSorted() as string ] ?? null }
									</th>
								) ) }
							</tr>
						) ) }
					</thead>
					<tbody>
						{ ( isLoading || isFetching ) ? (
							<TableSkeleton rows={ 15 } columns={ table.getAllColumns().length } />
						) : table.getRowModel().rows.length === 0 ? (
							<tr>
								<td colSpan={ table.getAllColumns().length } className="logmate-table-empty">
									No logs found
								</td>
							</tr>
						) : (
							table.getRowModel().rows.map( ( row ) => (
								<tr key={ row.id }>
									{ row.getVisibleCells().map( ( cell ) => (
										<td key={ cell.id }>
											{ flexRender( cell.column.columnDef.cell, cell.getContext() ) }
										</td>
									) ) }
								</tr>
							) )
						) }
					</tbody>
				</table>

				<div className="logmate-pagination">
					<button
						onClick={ () => table.setPageIndex( 0 ) }
						disabled={ ! table.getCanPreviousPage() }
						className="logmate-btn logmate-btn-secondary"
					>
						First
					</button>
					<button
						onClick={ () => table.previousPage() }
						disabled={ ! table.getCanPreviousPage() }
						className="logmate-btn logmate-btn-secondary"
					>
						Previous
					</button>
					<span>
						Page { table.getState().pagination.pageIndex + 1 } of { table.getPageCount() }
					</span>
					<button
						onClick={ () => table.nextPage() }
						disabled={ ! table.getCanNextPage() }
						className="logmate-btn logmate-btn-secondary"
					>
						Next
					</button>
					<button
						onClick={ () => table.setPageIndex( table.getPageCount() - 1 ) }
						disabled={ ! table.getCanNextPage() }
						className="logmate-btn logmate-btn-secondary"
					>
						Last
					</button>
				</div>
			</div>

			<Modal
				isOpen={ isModalOpen }
				onClose={ () => setIsModalOpen( false ) }
				onConfirm={ handleClearLogs }
				title="Clear Logs"
				message={ `Are you sure you want to clear ${ logTypeText }? This action cannot be undone.` }
				confirmText="Clear Logs"
				cancelText="Cancel"
				confirmButtonClass="logmate-btn-danger"
			/>
			<ExportLogsModal
				isOpen={ isExportModalOpen }
				onClose={ () => setIsExportModalOpen( false ) }
				logType={ logType }
			/>
		</>
	);
};

