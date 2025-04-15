<?php

namespace App\Entity;

use App\Repository\AssetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AssetRepository::class)]
#[ORM\Table(name: "assets")]
class Asset
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2)]
    #[Assert\NotBlank(message: "Bid price is required.")]
    private ?string $bid = null;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2)]
    #[Assert\NotBlank(message: "Ask price is required.")]
    private ?string $ask = null;

    #[ORM\Column(type: "integer", options: ["default" => 10])]
    private int $lotSize = 10;

    #[ORM\Column(type: "datetime")]
    #[Assert\NotBlank(message: "Date update is required.")]
    private ?\DateTimeInterface $dateUpdate = null;

    #[ORM\Column(type: "string", length: 10, unique: true, options: ["default" => "BTC/USD"])]
    #[Assert\NotBlank(message: "Asset name is required.")]
    private string $assetName = 'BTC/USD';

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBid(): ?string
    {
        return $this->bid;
    }

    public function setBid(string $bid): self
    {
        $this->bid = $bid;
        return $this;
    }

    public function getAsk(): ?string
    {
        return $this->ask;
    }

    public function setAsk(string $ask): self
    {
        $this->ask = $ask;
        return $this;
    }

    public function getLotSize(): int
    {
        return $this->lotSize;
    }

    public function setLotSize(int $lotSize): self
    {
        $this->lotSize = $lotSize;
        return $this;
    }

    public function getDateUpdate(): ?\DateTimeInterface
    {
        return $this->dateUpdate;
    }

    public function setDateUpdate(\DateTimeInterface $dateUpdate): self
    {
        $this->dateUpdate = $dateUpdate;
        return $this;
    }

    public function getAssetName(): string
    {
        return $this->assetName;
    }

    public function setAssetName(string $assetName): self
    {
        $this->assetName = $assetName;
        return $this;
    }
}
