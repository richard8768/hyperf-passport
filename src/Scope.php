<?php

namespace Richard\HyperfPassport;

use Hyperf\Contract\Arrayable;
use Richard\HyperfPassport\Contracts\Jsonable;

class Scope implements Arrayable, Jsonable
{

    /**
     * The name / ID of the scope.
     *
     * @var string
     */
    public string $id;

    /**
     * The scope description.
     *
     * @var string
     */
    public string $description;

    /**
     * Create a new scope instance.
     *
     * @param string $id
     * @param string $description
     * @return void
     */
    public function __construct($id, $description)
    {
        $this->id = $id;
        $this->description = $description;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

}
