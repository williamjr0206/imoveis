SELECT
    ca.proprietario,
    ca.imovel,
    ca.inquilino,
    ca.valor_aluguel,
    ca.dia_vencimento,
    p.mes_referencia,
    COALESCE(p.status, 'EM ABERTO') AS status_pagamento,
    COALESCE(p.valor_pago, 0)       AS valor_pago
FROM vw_contratos_ativos ca
LEFT JOIN pagamentos p
    ON p.contrato_id = ca.contrato_id
   AND p.mes_referencia = '2025-01-01'
WHERE p.id IS NULL
   OR p.status <> 'PAGO'
ORDER BY ca.proprietario, ca.imovel;
