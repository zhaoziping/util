<?php
namespace Simple\Util;

class Collection
{
    //数组
    protected $item = [];

    public function __construct(array $items = [])
    {
        $this->item = $items;
    }

    //获取所有的元素
    public function all()
    {
        return $this->item;
    }

    //获取数组的平均值
    public function avg()
    {
        if ($count = $this->count()) {
            return $this->sum() / $this->count();
        }
    }

    //数组分割成几块array_chuck
    public function chunk($size)
    {
        if ($size <= 0) {
            return new static;
        }

        $chunks = [];

        foreach (array_chunk($this->item, $size, true) as $chunk) {
            $chunks[] = $chunk;
        }

        return new static($chunks);
    }

    //多个数组合并成一个不支持递归
    public function collapse()
    {
        $results = [];
        foreach ($this->item as $values) {
            $results = array_merge($results, $values);
        }
        return new static($results);
    }

    //array_combine
    public function combine($arr)
    {
        return new static(array_combine($this->item, $arr));
    }

    //追加
    public function concat(array $arr)
    {
        $result = new static($this->item);

        foreach ($arr as $item) {
            $result->push($item);
        }

        return $result;
    }

    //生成笛卡尔乘积包含本类
    public function crossJoins(...$lists)
    {
        return new static($this->crossJoin($this->item, ...$lists));
    }

    //生成笛卡尔乘积不包含本类
    public function crossJoin(...$lists)
    {
        $results = [[]];
        foreach ($lists as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;

                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    //获取数组的数目
    public function count()
    {
        return count($this->item);
    }

    //生成差集基于value
    public function diff(array $arr)
    {
        return new static(array_diff($this->item, $arr));
    }

    //生成差集基于key=>value
    public function diffAssoc(array $arr)
    {
        return new static(array_diff_assoc($this->item, $arr));
    }

    //生成差集基于key
    public function diffKeys(array $arr)
    {
        return new static(array_diff_key($this->item, $arr));
    }
    //循环改变这个数组
    public function each(callable $callable)
    {
        foreach ($this->item as $key => $value) {
            if ($callable($value, $key) == false) {
                break;
            }
        }
        return $this;
    }
    //集合每一个元素的循环
    public function eachSpread(callable $callback)
    {
        return $this->each(function ($chunk) use ($callback) {
            return $callback(...$chunk);
        });
    }

    //校验数组的每一个元素是否可以通过检验
    public function every(callable $callback)
    {
        return count(array_filter($this->item, $callback)) == $this->count();
    }

    //过滤指定的key
    public function except($keys)
    {
        $original = &$this->item;

        $keys = (array)$keys;

        if (count($keys) === 0) {
            return null;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (array_key_exists($key, $this->item)) {
                unset($this->item[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
        return $this;
    }

    //数组过滤
    public function filter($callback = null)
    {
        if ($callback) {
            return new static(array_filter($this->item, $callback, ARRAY_FILTER_USE_BOTH));
        }
        return new static(array_filter($this->item));
    }

    //获取第一个元素的值
    public function first()
    {
        return current($this->item);
    }
    //遍历集合中的每个值传给回调
    public function flatMap(callable $callback)
    {
        return $this->map($callback)->collapse();
    }

    //展开数组不保留key
    public function flatten($depth = INF)
    {
        return new static(array_reduce($this->item, function ($result, $item) use ($depth) {
            if (!is_array($item)) {
                return array_merge($result, [$item]);
            } elseif ($depth === 1) {
                return array_merge($result, array_values($item));
            } else {
                return array_merge($result, (new static($item))->flatten($depth - 1)->all());
            }
        }, []));
    }

    //key和value进行互换
    public function flip()
    {
        return new static(array_flip($this->item));
    }

    //删除指定的key
    public function forget($keys)
    {
        foreach ((array)$keys as $key) {
            unset($this->item[$key]);
        }
        return $this;
    }
    public function forPage($page, $perPage)
    {
        return $this->slice(($page - 1) * $perPage, $perPage);
    }

    //获取指定的key的value如果不存在返回default
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->item)) {
            return $this->item[$key];
        }
        return $default;
    }

    //指定key分组
    public function groupBy($key)
    {
        $results = [];
        foreach ($this->item as $value) {
            $results[$value[$key]][] = $value;
        }
        return new static($results);
    }


    //是否存在指定的key
    public function has($keys)
    {
        return array_key_exists($keys, $this->item);
    }

    //拆分一维或者二维数组
    public function implode($key, $glue = null)
    {
        if (is_array($this->first())) {
            return implode($glue, $this->pluck($key)->all());
        }
        return implode($key, $this->item);
    }

    //求交集key=>value
    public function intersect($arr)
    {
        return new static(array_intersect($this->item, $arr));
    }

    //求交集key
    public function intersectByKeys($arr)
    {
        return new static(array_intersect_key($this->item, $arr));
    }

    //数组是否为空
    public function isEmpty()
    {
        return empty($this->item);
    }
    //数组是否不为空
    public function isNotEmpty()
    {
        return !empty($this->item);
    }

    //二维数组的某个key的value作为key
    public function keyBy($keyBy, $callback = null)
    {
        if ($callback) {
            return new static(array_combine(array_map($callback, array_column($this->item, $keyBy)), $this->item));
        }
        return new static(array_combine(array_column($this->item, $keyBy), $this->item));
    }

    //获取数组的所有key
    public function keys()
    {
        return new static(array_keys($this->item));
    }


    //获取最后一个元素的值
    public function last()
    {
        return current(array_reverse($this->item));
    }

    //类似array_map
    public function map(callable $callback)
    {
        $keys = array_keys($this->item);

        $items = array_map($callback, $this->item, $keys);

        return new static(array_combine($keys, $items));
    }


    public function mapWithKeys(callable $callback)
    {
        $result = [];

        foreach ($this->item as $key => $value) {
            $assoc = $callback($value, $key);

            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return new static($result);
    }

    //获取最大值
    public function max()
    {
        return max($this->item);
    }

    //获取最小值
    public function min()
    {
        return min($this->item);
    }

    //合并数组
    public function merge($arr)
    {
        return new static(array_merge($this->item, $arr));
    }
    //获取数组的中间值（大于这个数的和小于这个数的数组数目一样）
    public function median($key = null)
    {
        $count = $this->count();
        if ($count == 0) {
            return null;
        }
        $values = (isset($key) ? $this->pluck($key) : $this)->sort()->values();
        $middle = (int)$count / 2;
        if ($count % 2 == 0) {
            return $values->get($middle);
        }
        return (new static([$values->get($middle - 1), $values->get($middle)]))->avg();
    }

    //获取众数
    public function mode($key = null)
    {
        $count = $this->count();
        if ($count == 0) {
            return null;
        }
        $collection = isset($key) ? $this->pluck($key) : $this;
        $counts = [];
        $collection->each(function ($value) use (&$counts) {
            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
        });
        return (new self($counts))->sort()->keys()->last();
    }

    //创建由每隔 n 个元素组成的一个新集合
    public function nth($step, $offset = 0)
    {
        $new = [];

        $position = 0;

        foreach ($this->item as $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }

            $position++;
        }

        return new static($new);
    }

    //只保留指定的key
    public function only($keys)
    {
        return array_intersect_key($this->item, array_flip((array)$keys));
    }


    //切割数组
    public function partition($callback)
    {
        $partitions = [[], []];

        foreach ($this->item as $key => $item) {
            $partitions[(int)!$callback($item)][$key] = $item;
        }

        return new static($partitions);
    }
    //array_column
    public function pluck($key)
    {
        return new static(array_column($this->item, $key));
    }
    //删除最后一个元素
    public function pop()
    {
        return array_pop($this->item);
    }

    //数组头部添加元素
    public function prepend($key, $value)
    {
        return new static([$key => $value] + $this->item);
    }

    //获取并且删除指定key
    public function pull($key)
    {
        $value = $this->get($key);
        $this->forget($key);
        return $value;
    }

    //末尾追加值
    public function push($value)
    {
        $this->item[] = $value;
        return $this;
    }

    //末尾追加key/value
    public function put($key, $value)
    {
        $this->item[$key] = $value;
        return $this;
    }
    //随机返回一个value
    public function random()
    {
        return $this->get(array_rand($this->item));
    }

    //反转数组
    public function reverse()
    {
        return new static(array_reverse($this->item));
    }
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->item, $callback, $initial);
    }

    //查询key是否存在
    public function search($key)
    {
        return array_key_exists($key, $this->item);
    }
    //删除第一个元素
    public function shift()
    {
        array_shift($this->item);
        return $this;
    }

    //打乱数组
    public function shuffle()
    {
        shuffle($this->item);
        return $this;
    }

    //返回给定索引后面的部分
    public function slice($index,$length=null)
    {
        return new static(array_slice($this->item, $index,$length,true));
    }

    //对数组进行排序并且保留key
    public function sort($callback = null)
    {
        $items = $this->item;
        $callback ? uasort($items, $callback) : asort($items);
        return new static($items);
    }
    //根据回调的值对数组排序
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        $results = [];
        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($this->item as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        $descending ? arsort($results, $options)
            : asort($results, $options);

        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->item[$key];
        }

        return new static($results);
    }
    //根据回调的值对数组排序倒序
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, true);
    }
    //指定切分几组
    public function split($numberOfGroups)
    {
        if ($this->isEmpty()) {
            return new static;
        }

        $groupSize = ceil($this->count() / $numberOfGroups);

        return $this->chunk($groupSize);
    }
    //获取数组或者二维数组某个key的和
    public function sum($key = null)
    {
        if (is_null($key)) {
            return array_sum($this->item);
        }
        return array_sum(array_column($this->item, $key));
    }
    //返回给定数量的数组
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }
    //数组传递给回调不影响集合本身
    public function tap(callable $callback)
    {
        $callback(new static($this->items));

        return $this;
    }
    //改变集合的值
    public function transform(callable $callback)
    {
        $this->item = $this->map($callback)->all();

        return $this;
    }
    //通过回调在给定次数内创建一个新的集合
    public static function times($number, callable $callback = null)
    {
        if ($number < 1) {
            return new static;
        }

        if (is_null($callback)) {
            return new static(range(1, $number));
        }

        return (new static(range(1, $number)))->map($callback);
    }
    //转成数组
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof self ? $value->toArray() : $value;
        }, $this->item);
    }
    //转换成json
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
    //给定的数组添加到集合中
    public function union(array $items)
    {
        return new static($this->item + $items);
    }
    //数组去重
    public function unique()
    {
        return new static(array_unique($this->item));
    }
    //获取数组所有的value
    public function values()
    {
        return new static(array_values($this->item));
    }
    //当传入的第一个参数为 true 的时，将执行给定的回调
    public function when($value, callable $callback, callable $default = null)
    {
        if ($value) {
            return $callback($this);
        } elseif ($default) {
            return $default($this);
        }

        return $this;
    }


    //依赖symfony/var-dumper 包
    public function dd($flag = true)
    {
        if (function_exists("dd")){
            if ($flag){
                dd($this->toArray());
            }
            dd($this);
        }
    }
    //依赖symfony/var-dumper 包
    public function dump($flag = true)
    {
        if (function_exists("dump")){
            if ($flag){
                dump($this->toArray());
            }else{
                dump($this);
            }
        }
    }
    //是否可执行的回调
    protected function useAsCallable($value)
    {
        return !is_string($value) && is_callable($value);
    }
}