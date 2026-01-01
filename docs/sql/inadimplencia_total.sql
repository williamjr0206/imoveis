SELECT
    SUM(ca.valor_aluguel) AS total_inadimplente
FROM vw_contratos_ativos ca
LEFT JOIN pagamentos p
    ON p.contrato_id = ca.contrato_id
   AND p.mes_referencia = '2025-01-01'
WHERE p.id IS NULL
   OR p.status <> 'PAGO';
