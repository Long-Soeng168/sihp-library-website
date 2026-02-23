import AlertFlashMessage from '@/components/Alert/AlertFlashMessage';
import AllErrorsAlert from '@/components/Alert/AllErrorsAlert';
import GenerateRandomEmailButton from '@/components/Button/GerateRandomEmailButton';
import SubmitButton from '@/components/Button/SubmitButton';
import CheckboxCardOption from '@/components/Card/CheckboxCardOption';
import FormFileUpload from '@/components/Form/FormFileUpload';
import UploadedImage from '@/components/Form/UploadedImageDisplay';
import { FormCombobox } from '@/components/Input/FormCombobox';
import { FormErrorLabel } from '@/components/Input/FormErrorLabel';
import { FormField } from '@/components/Input/FormField';
import { FormLabel } from '@/components/Input/FormLabel';
import { ProgressWithValue } from '@/components/ProgressBar/progress-with-value';
import RadioSelect from '@/components/Section/RadioSelect';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import useTranslation from '@/hooks/use-translation';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface UserForm {
    name: string;
    name_kh: string;
    card_number: string;
    expired_at: string;
    email: string;
    phone: string;
    gender: string;
    password: string;
    title_type_code?: string;
    category_code?: string;
    password_confirmation: string;
    image: string | null;
    roles: string[];
}

export default function Create({ editData, readOnly }: { editData?: any; readOnly?: boolean }) {
    const [flashMessage, setFlashMessage] = useState<{ message: string; type: string }>({
        message: '',
        type: 'message',
    });

    const [files, setFiles] = useState<File[] | null>(null);
    const { roles, auth, types, userCategories } = usePage<any>().props;

    const { data, setData, post, processing, transform, progress, errors, reset } = useForm<UserForm>({
        name: editData?.name || '',
        name_kh: editData?.name_kh || '',
        card_number: editData?.card_number || '',
        expired_at: editData?.expired_at || '',
        email: editData?.email || '',
        phone: editData?.phone || '',
        gender: editData?.gender || '',
        title_type_code: editData?.title_type_code || '',
        category_code: editData?.category_code || '',
        password: '',
        password_confirmation: '',
        image: editData?.image || null,
        roles: editData?.roles?.map((r: any) => r.name) || [],
    });

    const handleRoleToggle = (roleName: string) => {
        const updatedRoles = data.roles.includes(roleName) ? data.roles.filter((r) => r !== roleName) : [...data.roles, roleName];

        setData('roles', updatedRoles);
    };

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        transform(() => ({ ...data, image: files ? files[0] : null }));

        if (editData?.id) {
            post(`/admin/users/${editData.id}/update`, {
                onSuccess: (page: any) => {
                    setFiles(null);
                    setFlashMessage({ message: page.props.flash?.success, type: 'success' });
                },
            });
        } else {
            post('/admin/users', {
                onSuccess: (page: any) => {
                    reset();
                    setFiles(null);
                    setFlashMessage({ message: page.props.flash?.success, type: 'success' });
                },
            });
        }
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Users', href: '/admin/users' },
        { title: editData?.name || 'Create', href: '#' },
    ];

    const [inputLanguage, setInputLanguage] = useState<'default' | 'khmer'>('default');
    const { t, currentLocale } = useTranslation();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <form onSubmit={onSubmit} className="form">
                <AlertFlashMessage
                    key={flashMessage.message}
                    type={flashMessage.type}
                    flashMessage={flashMessage.message}
                    setFlashMessage={setFlashMessage}
                />
                {errors && <AllErrorsAlert title="Please fix the following errors" errors={errors} />}

                <div className="sticky top-0">
                    <Tabs value={inputLanguage} onValueChange={(val: any) => setInputLanguage(val)}>
                        <TabsList className="mb-1 border bg-border/50 p-1 dark:border-white/20">
                            <TabsTrigger value="default" className="h-full dark:data-[state=active]:bg-white/20">
                                {t('Default')}
                            </TabsTrigger>
                            <TabsTrigger value="khmer" className="h-full dark:data-[state=active]:bg-white/20">
                                {t('Khmer')}
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>
                </div>
                {inputLanguage == 'khmer' ? (
                    <div className="form-field-container">
                        <FormField
                            required
                            id="name_kh"
                            name="name_kh"
                            label="Name Khmer"
                            value={data.name_kh}
                            onChange={(val) => setData('name_kh', val)}
                            error={errors.name_kh}
                        />
                    </div>
                ) : (
                    <div>
                        <div className="form-field-container mb-5">
                            <FormField
                                id="card_number"
                                name="card_number"
                                label="Card Number"
                                value={data.card_number}
                                onChange={(val) => setData('card_number', val)}
                                error={errors.card_number}
                            />
                            <FormField
                                type="date"
                                id="expired_at"
                                name="expired_at"
                                label="Expired Date"
                                value={data.expired_at}
                                onChange={(val) => setData('expired_at', val)}
                                error={errors.expired_at}
                                description={t('Auto-calculated from category if blank.')}
                            />
                        </div>
                        <div className="form-field-container mb-5">
                            {types?.length > 0 && (
                                <FormCombobox
                                    name="title_type_code"
                                    label="Honorific (គោរមងារ)"
                                    options={types.map((item: any) => ({
                                        value: item.code,
                                        label: `${item.name} (${item.name_kh})`,
                                    }))}
                                    value={data.title_type_code || ''}
                                    onChange={(val) => setData('title_type_code', val)}
                                    error={errors.title_type_code}
                                />
                            )}
                            {userCategories?.length > 0 && (
                                <FormCombobox
                                    name="category_code"
                                    label="User Category"
                                    options={[
                                        {
                                            value: null,
                                            label: t(`NA`),
                                        },
                                        ...userCategories.map((item: any) => ({
                                            value: item.code,
                                            label: `${currentLocale == 'kh' ? (item?.name_kh ?? item?.name) : item?.name}`,
                                        })),
                                    ]}
                                    value={data.category_code || ''}
                                    onChange={(val) => setData('category_code', val)}
                                    error={errors.category_code}
                                />
                            )}
                        </div>
                        <div className="form-field-container">
                            <FormField
                                required
                                id="name"
                                name="name"
                                label="Name"
                                value={data.name}
                                onChange={(val) => setData('name', val)}
                                error={errors.name}
                            />

                            <FormField
                                required
                                type="number"
                                id="phone"
                                name="phone"
                                label="Phone Number"
                                value={data.phone}
                                onChange={(val) => setData('phone', val)}
                                error={errors.phone}
                            />

                            <div>
                                <FormField
                                    required
                                    id="email"
                                    name="email"
                                    label="Email"
                                    value={data.email}
                                    onChange={(val) => setData('email', val)}
                                    error={errors.email}
                                />
                                <GenerateRandomEmailButton setData={setData} />
                            </div>

                            <div className="mb-4 grid content-start">
                                <FormLabel id="gender" label="Gender" required={true} />
                                <RadioSelect
                                    name="gender"
                                    options={[
                                        { label: 'Male', value: 'male' },
                                        { label: 'Female', value: 'female' },
                                        { label: 'Other', value: 'other' },
                                    ]}
                                    value={data.gender}
                                    onChange={(val) => setData('gender', val)}
                                />
                                <FormErrorLabel error={errors.gender} />
                            </div>

                            <FormField
                                required={editData?.id ? false : true}
                                id="password"
                                name="password"
                                label={editData?.id ? 'New Password' : 'Password'}
                                value={data.password}
                                onChange={(val) => setData('password', val)}
                                error={errors.password}
                                type="password"
                            />

                            <FormField
                                required={editData?.id ? false : true}
                                id="password_confirmation"
                                name="password_confirmation"
                                label={editData?.id ? 'Confirm New Password' : 'Confirm Password'}
                                value={data.password_confirmation}
                                onChange={(val) => setData('password_confirmation', val)}
                                error={errors.password_confirmation}
                                type="password"
                            />
                        </div>

                        {roles.length > 0 && (
                            <div className="my-8 grid content-start gap-2">
                                <FormLabel id="roles" label="Roles" required />
                                <div className="flex flex-wrap gap-3">
                                    {roles.map(({ name }: { name: string }) => {
                                        const isSuperAdmin = auth.roles.includes('Super Admin');
                                        const isAdmin = auth.roles.includes('Admin');

                                        // If not Super Admin:
                                        if (!isSuperAdmin) {
                                            // Admins can't assign Super Admin
                                            if (isAdmin && name === 'Super Admin') return null;

                                            // Non-admins can't assign Admin or Super Admin
                                            if (!isAdmin && (name === 'Super Admin' || name === 'Admin')) return null;
                                        }

                                        return (
                                            <CheckboxCardOption
                                                className="max-w-[130px] rounded py-2"
                                                checkBoxClassName="top-0 right-0"
                                                key={name}
                                                option={{ value: name, label: name, icon: () => null }} // no icon needed for roles
                                                checked={data.roles.includes(name)}
                                                onChange={(value) => handleRoleToggle(value)}
                                            />
                                        );
                                    })}
                                </div>
                                <FormErrorLabel error={errors.roles} />
                            </div>
                        )}

                        <div>
                            <div className={cn('form-field-container', !editData?.image && 'md:grid-cols-1')}>
                                <FormFileUpload key={editData?.image} id="image" label="Profile Image" files={files} setFiles={setFiles} />
                                {editData?.image && (
                                    <UploadedImage
                                        containerClassName="mt-0"
                                        imageContainerClassName="flex-1"
                                        label="Uploaded image"
                                        images={editData?.image}
                                        basePath="/assets/images/users/thumb/"
                                    />
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {progress && <ProgressWithValue value={progress.percentage} position="start" />}

                {!readOnly && <SubmitButton processing={processing} />}
            </form>
        </AppLayout>
    );
}
