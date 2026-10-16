<?php
/**
 * Card de produto da vitrine.
 *
 * @var array<string, mixed> $produto
 */
require_once __DIR__ . '/icons.php';

$cardName = (string) ($produto['nome'] ?? 'Produto');
$cardUrl = app_url('/produtos/' . rawurlencode((string) ($produto['public_slug'] ?? '')));
$cardStock = (int) ($produto['estoque_atual'] ?? 0);
$cardPrice = isset($produto['preco']) ? (float) $produto['preco'] : null;
$cardCompare = isset($produto['compare_at_price']) && $produto['compare_at_price'] !== null ? (float) $produto['compare_at_price'] : null;
$cardCategory = (string) ($produto['categoria_nome'] ?? '');
$cardBrand = trim((string) ($produto['brand_name'] ?? ''));
$cardLow = (string) ($produto['availability_status'] ?? '') === 'low_stock';
?>
<article class="product-card">
  <a class="product-card__media" href="<?= e($cardUrl) ?>" tabindex="-1" aria-hidden="true">
    <?php if (!empty($produto['imagem'])): ?>
      <img src="<?= e(client_product_image_url((string) $produto['imagem'])) ?>" alt="" loading="lazy" />
    <?php else: ?>
      <span class="product-card__placeholder"><?= bmc_icon(bmc_department_icon($cardCategory . ' ' . $cardName), 'icon icon--xl') ?></span>
    <?php endif; ?>
    <?php if ($cardStock <= 0): ?>
      <span class="product-card__flag product-card__flag--out">Sem estoque</span>
    <?php elseif ($cardLow): ?>
      <span class="product-card__flag">Últimas unidades</span>
    <?php endif; ?>
  </a>
  <div class="product-card__body">
    <p class="product-card__meta"><?= e($cardBrand !== '' ? $cardBrand : $cardCategory) ?></p>
    <h3 class="product-card__name"><a href="<?= e($cardUrl) ?>"><?= e($cardName) ?></a></h3>
  </div>
  <div class="product-card__buy">
    <?= bmc_price_tag($cardPrice, 'md', $cardCompare) ?>
    <?php if ($cardStock > 0 && $cardPrice !== null && $cardPrice > 0): ?>
      <form method="POST" action="<?= e(app_url('/carrinho/adicionar')) ?>" data-commerce-product-form>
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int) $produto['id'] ?>" />
        <input type="hidden" name="quantity" value="1" />
        <button class="btn btn--primary btn--block" type="submit" data-commerce-add-cart>
          <?= bmc_icon('cart') ?> Adicionar
        </button>
      </form>
    <?php else: ?>
      <a class="btn btn--ghost btn--block" href="<?= e($cardUrl) ?>">Avise-me</a>
    <?php endif; ?>
  </div>
</article>
