<?php

namespace App\Entity;

use App\Repository\TradeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TradeRepository::class)]
#[ORM\Table(name: "trades")]
class Trade
{

    const STATUS_OPEN = 'open';
    const STATUS_WON = 'won';
    const STATUS_LOSE = 'lose';
    const STATUS_TIE = 'tie';
    const STATUS_CLOSED = 'closed';
    const BUY = 'buy';
    const SELL = 'sell';
    

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "opened_by_agent_id", referencedColumnName: "id", nullable: false)]
    private ?User $agentId = null;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2, nullable: true)]
    private ?float $tradeSize = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $lotCount = null;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2, nullable: true)]
    private ?float $pnl = null;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2, nullable: true)]
    private ?float $payout = null;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2, nullable: true)]
    private ?float $usedMargin = null;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2, nullable: true)]
    private ?float $entryRate = null;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2, nullable: true)]
    private ?float $closeRate = null;

    #[ORM\Column(type: "datetime", options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTime $dateCreated;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $dateClose = null;

    #[ORM\Column(type: "string", length: 20, nullable: true, options: ["default" => self::STATUS_OPEN])]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2, nullable: true)]
    private ?float $stopLoss = null;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2, nullable: true)]
    private ?float $takeProfit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAgentId(): ?User
    {
        return $this->agentId;
    }

    public function setAgentId(?User $agentId): self
    {
        $this->agentId = $agentId;
        return $this;
    }

    public function getTradeSize(): ?float
    {
        return $this->tradeSize;
    }

    public function setTradeSize(?float $tradeSize): self
    {
        $this->tradeSize = $tradeSize;
        return $this;
    }

    public function getLotCount(): ?int
    {
        return $this->lotCount;
    }

    public function setLotCount(?int $lotCount): self
    {
        $this->lotCount = $lotCount;
        return $this;
    }

    public function getPnl(): ?float
    {
        return $this->pnl;
    }

    public function setPnl(?float $pnl): self
    {
        $this->pnl = $pnl;
        return $this;
    }

    public function getPayout(): ?float
    {
        return $this->payout;
    }

    public function setPayout(?float $payout): self
    {
        $this->payout = $payout;
        return $this;
    }

    public function getUsedMargin(): ?float
    {
        return $this->usedMargin;
    }

    public function setUsedMargin(?float $usedMargin): self
    {
        $this->usedMargin = $usedMargin;
        return $this;
    }

    public function getEntryRate(): ?float
    {
        return $this->entryRate;
    }

    public function setEntryRate(?float $entryRate): self
    {
        $this->entryRate = $entryRate;
        return $this;
    }

    public function getCloseRate(): ?float
    {
        return $this->closeRate;
    }

    public function setCloseRate(?float $closeRate): self
    {
        $this->closeRate = $closeRate;
        return $this;
    }

    public function getDateCreated(): \DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    public function getDateClose(): ?\DateTime
    {
        return $this->dateClose;
    }

    public function setDateClose(?\DateTime $dateClose): self
    {
        $this->dateClose = $dateClose;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getStopLoss(): ?float
    {
        return $this->stopLoss;
    }

    public function setStopLoss(?float $stopLoss): self
    {
        $this->stopLoss = $stopLoss;
        return $this;
    }

    public function getTakeProfit(): ?float
    {
        return $this->takeProfit;
    }

    public function setTakeProfit(?float $takeProfit): self
    {
        $this->takeProfit = $takeProfit;
        return $this;
    }
}
