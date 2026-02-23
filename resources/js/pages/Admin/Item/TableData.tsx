import TableCellAvatar from '@/components/Avatar/TableCellAvatar';
import DeleteItemButton from '@/components/Button/DeleteItemButton';
import EditItemButton from '@/components/Button/EditItemButton';
import RecoverItem from '@/components/Button/RecoverItemButton';
import ViewItemButton from '@/components/Button/ViewItemButton';
import ViewItemPhysicalCopiesButton from '@/components/Button/ViewItemPhysicalCopiesButton';
import NoDataDisplay from '@/components/NoDataDisplay';
import TableCellActions from '@/components/Table/TableCellActions';
import TableCellBadge from '@/components/Table/TableCellBadge';
import TableCellDate from '@/components/Table/TableCellDate';
import TableCellText from '@/components/Table/TableCellText';
import TableHeadWithSort from '@/components/Table/TableHeadWithSort';
import { Table, TableBody, TableCell, TableHeader, TableRow } from '@/components/ui/table';
import { usePage } from '@inertiajs/react';

const TableData = () => {
    const { tableData } = usePage<any>().props;
    return (
        <>
            <div className="table-data-container">
                <Table>
                    <TableHeader className="table-header">
                        <TableRow>
                            <TableHeadWithSort field="id" label="ID" />
                            <TableHeadWithSort label="Action" />
                            <TableHeadWithSort label="Image" />
                            <TableHeadWithSort field="name" label="Name" />
                            <TableHeadWithSort field="name_kh" label="Name Khmer" />
                            <TableHeadWithSort field="status" label="Status" />
                            <TableHeadWithSort field="category_code" label="Category" />
                            <TableHeadWithSort field="file_type_code" label="File Type" />
                            <TableHeadWithSort field="total_view_count" label="Total Views" />
                            <TableHeadWithSort field="total_read_count" label="Total Reads" />
                            <TableHeadWithSort field="total_download_count" label="Total Downloads" />
                            <TableHeadWithSort field="total_checkouts" label="Total Checkouts" />
                            <TableHeadWithSort field="created_at" label="Created at" />
                            <TableHeadWithSort field="created_by" label="Created by" />
                            <TableHeadWithSort field="updated_at" label="Updated at" />
                            <TableHeadWithSort field="updated_by" label="Updated by" />
                        </TableRow>
                    </TableHeader>
                    <TableBody className="table-body rounded-md">
                        {tableData?.data?.map((item: any, index: number) => (
                            <TableRow className="table-row" key={item.id}>
                                <TableCellText value={item.id} />
                                <TableCellActions>
                                    {item.deleted_at ? (
                                        <RecoverItem
                                            deleted_at={item.deleted_at}
                                            recoverPath={`/admin/items/${item.id}/recover`}
                                            permission="user update"
                                        />
                                    ) : (
                                        <>
                                            {/* Edit Dialog */}
                                            <EditItemButton url={`/admin/items/${item.id}/edit`} permission="item update" />

                                            {/* View Dialog */}
                                            <ViewItemPhysicalCopiesButton
                                                url={`/admin/items/${item.id}?view_physical_copies=1`}
                                                permission="item view"
                                            />
                                            <ViewItemButton url={`/admin/items/${item.id}`} permission="item view" />

                                            {/* Delete Item */}
                                            <DeleteItemButton deletePath="/admin/items/" id={item.id} permission="item delete" />
                                        </>
                                    )}
                                </TableCellActions>
                                <TableCell>
                                    <TableCellAvatar
                                        className="rounded-none border-none object-contain"
                                        alt={item.name}
                                        image={`/assets/images/items/thumb/${item.thumbnail}`}
                                    />
                                </TableCell>
                                <TableCellText value={item.name} />
                                <TableCellText value={item.name_kh} />
                                <TableCellBadge value={item.status} className="capitalize" variant={item.status} />
                                <TableCellText value={item.category?.name} />
                                <TableCellText value={item.file_type?.name} />
                                <TableCellText value={item.total_view_count} />
                                <TableCellText value={item.total_read_count} />
                                <TableCellText value={item.total_download_count} />
                                <TableCellText value={item.total_checkouts} />
                                <TableCellDate value={item.created_at} />
                                <TableCellText value={item.created_user?.name} />
                                <TableCellDate value={item.updated_at} />
                                <TableCellText value={item.updated_user?.name} />
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
            {tableData?.data?.length < 1 && <NoDataDisplay />}
        </>
    );
};

export default TableData;
