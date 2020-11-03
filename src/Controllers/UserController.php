<?php

namespace Encore\Admin\Controllers;

use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Tree;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Auth\Database\Administrator;
use App\Models\Company;
use App\Helper\GlobalParam;

class UserController extends AdminController
{
    /**
     * {@inheritdoc}
     */
    protected function title()
    {
        return trans('admin.administrator');
    }

    /**
     * Index interface.
     *
     * @return Content
     */
    // public function index(Content $content)
    // {
    //     $content->header('管理員');
    //     $content->description('列表');
    //     $content->row(function (Row $row) {
    //         $row->column(12, $this->treeView());
    //     });

    //     return $content;
    // }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $userModel = config('admin.database.users_model');
        $roleModel = config('admin.database.roles_model');

        $grid = new Grid(new $userModel());
        $grid->column('username', trans('admin.username'));
        $grid->column('name', trans('admin.name'));
        $grid->column('roles', trans('admin.roles'))->pluck('name')->label();
        if (!\Admin::user()->isRole('operator'))
        {
            $grid->column('belong_company', trans('admin.belongCompany'))->display(function($v){
                return GlobalParam::Company()[$v]??"";
            });
        }
        else;
        $grid->column('assign_company', trans('admin.assignCompany'))->display(function($v){
            return GlobalParam::Company()[$v]??"";
        });
        // $grid->column('created_at', trans('admin.created_at'));
        // $grid->column('updated_at', trans('admin.updated_at'));

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            if ($actions->getKey() == 1) {
                $actions->disableDelete();
            }
            $actions->disableView();
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->batch(function (Grid\Tools\BatchActions $actions) {
                $actions->disableDelete();
            });
        });

        if (\Admin::user()->isRole('agent'))
        {
            // $company = Company::where('_id', \Admin::user()->belong_company)
            //                  ->with('children.children.children.children.children')
            //                  ->get();
            // $oplist = [];
            // Company::flatChildren($oplist, $company);
            // $iplist = collect($oplist)->pluck('_id');
            // $grid->model()->whereIn('belong_company', $iplist);

            // //

            $companies = Company::selectOptions(function($q){
                return $q->with('children.children.children.children.children')->where('_id', \Admin::user()->belong_company);
            }, null);
            $grid->model()->whereIn('belong_company', array_keys($companies));
        }
        else if (\Admin::user()->isRole('operator'))
        {
            $grid->model()->where('assign_company', \Admin::user()->assign_company);
        }
        else;

        $grid->filter(function ($filter) use($roleModel){
            $filter->disableIdFilter();
            $filter->like('username', trans('admin.username'));
            if (\Admin::user()->isRole('administrator'))
            {
                $filter->equal('belong_company', trans('admin.belongCompany'))
                       ->select(Company::selectOptions(null,null));
            }
            else;
            if (!\Admin::user()->isRole('administrator'))
            {
                $filter->equal('assign_company', trans('admin.assignCompany'))
                       ->select(Company::selectOptions(function($q){
                            return $q->with('children.children.children.children.children')
                                     ->where('_id', \Admin::user()->belong_company);
                        }, null));
            }
            else
            {
                $filter->equal('assign_company', trans('admin.assignCompany'))
                       ->select(Company::selectOptions(function($q){
                            return $q->with('children.children.children.children.children');
                        }, null));
            }

        });
        $grid->disableCreateButton(false);
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        $userModel = config('admin.database.users_model');

        $show = new Show($userModel::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('username', trans('admin.username'));
        $show->field('name', trans('admin.name'));
        $show->field('roles', trans('admin.roles'))->as(function ($roles) {
            return $roles->pluck('name');
        })->label();
        $show->field('permissions', trans('admin.permissions'))->as(function ($permission) {
            return $permission->pluck('name');
        })->label();
        $show->field('created_at', trans('admin.created_at'));
        $show->field('updated_at', trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        $userModel = config('admin.database.users_model');
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $userModel());

        $userTable = config('admin.database.users_table');
        $connection = config('admin.database.connection');

        $form->display('id', 'ID');
        $form->text('username', trans('admin.username'))
            ->creationRules(['required', "unique:{$connection}.{$userTable}"])
            ->updateRules(['required', "unique:{$connection}.{$userTable},username,{{id}}"]);

        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'));
        $form->password('password', trans('admin.password'))->rules('required|confirmed');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });
        $form->password('secret_key', trans('admin.secret_key'));

        $form->ignore(['password_confirmation']);

        if (\Admin::user()->isRole('administrator')) {
            $form->select('belong_company', trans('admin.belongCompany'))->options(Company::selectOptions());
        } else {
            if (\Admin::user()->isRole('operator')) {
                $form->hidden('belong_company')->default(\Admin::user()->belong_company);
            } else {
                $form->select('belong_company', trans('admin.belongCompany'))->options(Company::selectOptions(function($q){
                    return $q->with('children.children.children.children.children')->where('_id', \Admin::user()->belong_company);
                }, null));
            }
        }

        if (\Admin::user()->isRole('administrator')) {
            $form->select('assign_company', trans('admin.assignCompany'))->options(Company::selectOptions(function($q){
                return $q->with('children.children.children.children.children');
            }, null));
            $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));
        } else {
            if (\Admin::user()->isRole('operator')) {
                $form->hidden('assign_company')->default(\Admin::user()->assign_company);
            } else {
                $form->select('assign_company', trans('admin.assignCompany'))->options(Company::selectOptions(function($q){
                    return $q->with('children.children.children.children.children')->where('_id', \Admin::user()->belong_company);
                }, null));
                $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::where('slug', '!=', 'administrator')->pluck('name', 'id'));
            }
        }

        //$form->multipleSelect('permissions', trans('admin.permissions'))->options($permissionModel::all()->pluck('name', 'id'));

        // $form->display('created_at', trans('admin.created_at'));
        // $form->display('updated_at', trans('admin.updated_at'));

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = bcrypt($form->password);
            }
            # 密钥
            if ($form->secret_key && $form->model()->secret_key != $form->secret_key) {
                $form->secret_key = bcrypt($form->secret_key);
            }
        });

        return $form;
    }

    /**
     * @return \Encore\Admin\Tree
     */
    protected function treeView()
    {
        return Administrator::tree(function (Tree $tree) {
            // $tree->disableCreate();
            $tree->branch(function ($branch) {
                // \Log::info($branch);
                // $vals = explode(',', $branch['keywords']);
                // $keywords = implode('&nbsp;', array_map(function($val){
                //     return "<span class='label label-info'>$val</span>";
                // }, $vals));
                $payload = "<i class='fa fa-bars'></i>&nbsp;<strong>{$branch['name']}</strong>&nbsp;&nbsp;<label>角色：{$branch['roles']}</label>";

                return $payload;
            });
            // ->query(function($q){
            //     return $q->where('id', \Admin::user()->id);
            // });
        });
    }
}
