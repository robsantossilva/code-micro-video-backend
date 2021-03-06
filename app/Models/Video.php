<?php

namespace App\Models;

use App\Models\Traits\UploadFiles;
use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use SoftDeletes, Uuid, UploadFiles;

    const RATING_LIST = ['L', '10', '12', '14', '16', '18'];
    const RELATED_TABLES = [
        'categories' => 'categories_id',
        'genres' => 'genres_id'
    ];

    protected   $fillable = [
        'title',
        'description',
        'year_launched',
        'opened',
        'rating',
        'duration'
    ];

    protected   $dates = ['deleted_at'];

    public      $incrementing = false;

    protected   $keyType = 'string';

    protected   $casts = [
        'id' => 'string',
        'title' => 'string',
        'description' => 'string',
        'year_launched' => 'integer',
        'opened' => 'boolean',
        'rating' => 'string',
        'duration' => 'integer'
    ];

    public static $fileFields = ['video_file'];

    public static function create(array $attributes = [])
    {
        $files = self::extractFiles($attributes);
        try {
            \DB::beginTransaction();
            /** @var Video $obj */
            $obj = static::query()->create($attributes);
            static::handleRelations($obj, $attributes);
            $obj->load(array_keys(self::relatedTables()))->refresh();
            $obj->uploadFiles($files);

            \DB::commit();
            return $obj;
        } catch (\Exception $e) {
            if (isset($obj)) {
                //excluir arquivos de upload
            }
            \DB::rollBack();
            throw $e;
        }
    }

    public function update(array $attributes = [], array $options = [])
    {
        try {
            \DB::beginTransaction();
            $saved = parent::update($attributes, $options);
            static::handleRelations($this, $attributes);
            $this->load(array_keys(self::relatedTables()))->refresh();
            if($saved){
                //uploads aqui
                //excluir antigos
            }            
            \DB::commit();
            return $saved;

        } catch (\Exception $e) {
            //excluir arquivos de uploads
            \DB::rollBack();
            throw $e;
        }
    }

    protected static function relatedTables() : array
    {
        return self::RELATED_TABLES;
    }

    public static function handleRelations($obj, array $attributes) {
        foreach(self::relatedTables() as $table => $field){
            if(isset($attributes[$field])){
                $obj->$table()->sync($attributes[$field]);
            }            
        }
    }

    //php artisan make:migration create_genre_video_table
    //php artisan make:migration create_category_video_table

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTrashed();
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class)->withTrashed();
    }

    protected function uploadDir()
    {
       return $this->id; 
    }
}
