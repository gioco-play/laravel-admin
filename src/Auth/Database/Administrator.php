<?php

namespace GiocoPlus\Admin\Auth\Database;

use GiocoPlus\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Support\Facades\Request;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;

/**
 * Class Administrator.
 *
 * @property Role[] $roles
 */
class Administrator extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasPermissions;
    use DefaultDatetimeFormat;
    use AdminTree;

    # 修正 Breaking change on eager loading when key is a string
    protected $keyType = 'string';

    protected $fillable = ['username', 'password', 'name', 'avatar'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $connection = config('admin.database.connection') ?: config('database.default');

        $this->setConnection($connection);

        $this->setTable(config('admin.database.users_table'));

        parent::__construct($attributes);
        # tree
        $this->setParentColumn('parent_id');
        $this->setOrderColumn('sort_order');
        $this->setTitleColumn('name');
    }

    /**
     * Get avatar attribute.
     *
     * @param string $avatar
     *
     * @return string
     */
    public function getAvatarAttribute($avatar)
    {
        if (url()->isValidUrl($avatar)) {
            return $avatar;
        }

        $disk = config('admin.upload.disk');

        if ($avatar && array_key_exists($disk, config('filesystems.disks'))) {
            return Storage::disk(config('admin.upload.disk'))->url($avatar);
        }

        $default = config('admin.default_avatar') ?: '/vendor/laravel-admin/AdminLTE/dist/img/user2-160x160.jpg';

        return admin_asset($default);
    }

    /**
     * A user has and belongs to many roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        $pivotTable = config('admin.database.role_users_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'role_id');
    }

    /**
     * A User has and belongs to many permissions.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        $pivotTable = config('admin.database.user_permissions_table');

        $relatedModel = config('admin.database.permissions_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'permission_id');
    }

    /**
     * Company
     *
     * @return void
     */
    public function belongToCompany() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            $model->roles()->detach();

            $model->permissions()->detach();
        });

        static::saving(function (Model $branch) {
            $parentColumn = $branch->getParentColumn();

            if (Request::has($parentColumn) && Request::input($parentColumn) == $branch->getKey()) {
                throw new \Exception(trans('admin.parent_select_error'));
            }

            if (Request::has('_order')) {
                $order = Request::input('_order');

                Request::offsetUnset('_order');

                static::tree()->saveOrder($order);

                return false;
            }

            foreach ($branch->attributes as $key => $value) {
                $branch->{$key} = empty($value) ? null : $value;
            }

            // 根據belong company 更新operator_code
            $action = isset(request()->all()['action']) ? request()->all()['action'] : 'N';
            if ('Y' === $action)
            {
                $belong = $branch->attributes['belong_company'];
                $opCode = Company::where('id', $belong)->select('operator_code')->first();
                Administrator::where('belong_company', $belong)->update(['operator_code' => $opCode['operator_code']]);
            }
            else;

            return $branch;
        });

        self::creating(function ($model) {
            $model->id = (string) \UUID::generate(4);
        });
    }

    /**
     * 取得營商代碼
     * 根據當前使用者的“公司別”取出營商
     * @return void
     */
    public function merchants() {

        if (\Admin::user()->isRole('administrator')) {
            return Company::select('name', 'code')->where('type', 'merchant')->orderBy('name')->get()->toArray();
        } else if (\Admin::user()->isRole('merchant')) {
            return Company::select('name', 'code')->where('assign_company', \Admin::user()->assign_company)->get()->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)->toArray();
        } else {
            // 代理、業務、客服
            $companies = Company::selectOptions(function($q){
                return $q->with('children.children.children.children.children')->where('id', \Admin::user()->belong_company);
            }, null);
            return Company::select('name', 'code')->whereIn('assign_company', array_keys($companies))->get()->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE)->toArray();
        }
    }
}
