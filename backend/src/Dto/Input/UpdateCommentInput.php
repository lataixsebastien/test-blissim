<?php

declare(strict_types=1);

namespace App\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateCommentInput
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 120, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
    public string $author;

    #[Assert\NotBlank(message: 'Le commentaire est obligatoire.')]
    #[Assert\Length(max: 2000, maxMessage: 'Le commentaire ne doit pas dépasser {{ limit }} caractères.')]
    public string $content;

    public function __construct(string $author = '', string $content = '')
    {
        $this->author = trim($author);
        $this->content = trim($content);
    }
}
