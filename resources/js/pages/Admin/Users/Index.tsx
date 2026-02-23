import ExportButton from '@/components/Button/ExportButton';
import NewItemButton from '@/components/Button/NewItemButton';
import RefreshButton from '@/components/Button/RefreshButton';
import PaginationTabs from '@/components/Pagination/PaginationTabs';
import TableDataSearch from '@/components/Search/TableDataSearch';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import FilterData from './FilterData';
import FilterRole from './FilterRole';
import TableData from './TableData';

const Index = () => {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        {
            title: 'Users',
            href: '/admin/users',
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <>
                <div className="flex flex-wrap items-center justify-between gap-2 px-2 pt-6 pb-5">
                    <div className="flex w-full gap-2 md:w-auto">
                        <FilterData />
                        <TableDataSearch />
                        <RefreshButton />
                    </div>
                    <div className="flex w-full justify-end gap-2 md:w-auto">
                        {/* Add New Dialog */}
                        <ExportButton endpoint="/users-export" />
                        <NewItemButton url="/admin/users/create" permission="user create" />
                    </div>
                </div>
                <FilterRole />
                <TableData />
                <PaginationTabs />
            </>
        </AppLayout>
    );
};

export default Index;
