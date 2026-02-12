import React from 'react';

interface TableSkeletonProps {
	rows?: number;
	columns?: number;
}

export const TableSkeleton: React.FC<TableSkeletonProps> = ( { rows = 10, columns = 6 } ) => {
	return (
		<>
			{ Array.from( { length: rows } ).map( ( _, rowIndex ) => (
				<tr key={ `skeleton-row-${ rowIndex }` }>
					{ Array.from( { length: columns } ).map( ( _, colIndex ) => (
						<td key={ `skeleton-cell-${ rowIndex }-${ colIndex }` }>
							<div className="logmate-skeleton">
								<div className="logmate-skeleton-line" style={ {
									width: colIndex === 2 ? '90%' : colIndex === 3 ? '70%' : colIndex === 0 || colIndex === 1 ? '60px' : colIndex === 4 ? '50px' : '120px',
								} } />
							</div>
						</td>
					) ) }
				</tr>
			) ) }
		</>
	);
};

