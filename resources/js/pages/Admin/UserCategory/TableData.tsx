import DeleteItemButton from '@/components/Button/DeleteItemButton';
import EditItemButton from '@/components/Button/EditItemButton';
import RecoverItem from '@/components/Button/RecoverItemButton';
import ViewItemButton from '@/components/Button/ViewItemButton';
import NoDataDisplay from '@/components/NoDataDisplay';
import TableCellActions from '@/components/Table/TableCellActions';
import TableCellDate from '@/components/Table/TableCellDate';
import TableCellText from '@/components/Table/TableCellText';
import TableHeadWithSort from '@/components/Table/TableHeadWithSort';
import { Table, TableBody, TableHeader, TableRow } from '@/components/ui/table';
import useTranslation from '@/hooks/use-translation';
import { usePage } from '@inertiajs/react';

const TableData = () => {
    const { tableData } = usePage<any>().props;
    const { t, currentLocale } = useTranslation();

    return (
        <>
            <div className="table-data-container">
                <Table>
                    <TableHeader className="table-header">
                        <TableRow>
                            <TableHeadWithSort field="id" label="ID" />
                            <TableHeadWithSort label="Action" />
                            <TableHeadWithSort field="code" label="Code" />
                            <TableHeadWithSort field="name" label="Name" />
                            <TableHeadWithSort field="user_category_type_code" label="Type" />
                            <TableHeadWithSort field="enrollment_period_months" label="Period (Months)" />
                            <TableHeadWithSort field="enrollment_fee" label="Fee" />
                            <TableHeadWithSort field="created_at" label="Created at" />
                            <TableHeadWithSort field="created_by" label="Created by" />
                            <TableHeadWithSort field="updated_at" label="Updated at" />
                            <TableHeadWithSort field="updated_by" label="Updated by" />
                        </TableRow>
                    </TableHeader>
                    <TableBody className="table-body rounded-md">
                        {tableData?.data?.map((item: any) => (
                            <TableRow className="table-row" key={item.id}>
                                <TableCellText value={item.id} />
                                <TableCellActions>
                                    {item.deleted_at ? (
                                        <RecoverItem
                                            deleted_at={item.deleted_at}
                                            recoverPath={`/admin/user-categories/${item.id}/recover`}
                                            permission="user_category update"
                                        />
                                    ) : (
                                        <>
                                            <EditItemButton url={`/admin/user-categories/${item.id}/edit`} permission="user_category update" />
                                            <ViewItemButton url={`/admin/user-categories/${item.id}`} permission="user_category view" />
                                            <DeleteItemButton deletePath="/admin/user-categories/" id={item.id} permission="user_category delete" />
                                        </>
                                    )}
                                </TableCellActions>
                                <TableCellText value={item.code} className="font-mono font-bold" />
                                <TableCellText value={currentLocale === 'kh' ? item.name_kh || item.name : item.name} />
                                <TableCellText value={item.user_category_type_code || t('NA')} />
                                <TableCellText value={`${item.enrollment_period_months} ${t('Months')}`} />
                                <TableCellText value={Number(item.enrollment_fee).toLocaleString()} className="font-semibold text-primary" />
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
