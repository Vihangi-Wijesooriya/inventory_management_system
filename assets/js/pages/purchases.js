initTxn({
  resource: 'purchases',
  partyResource: 'suppliers',
  partyField: 'supplier_id',
  partyName: 'supplier_name',
  dateField: 'purchase_date',
  hasCost: true,
  labels: {
    partyLabel: 'Supplier',
    noParty: '—',
    deleteConfirm: 'Delete this purchase? Added stock will be reverted.',
    deleted: 'Purchase deleted, stock reverted.',
    saved: 'Purchase saved.',
  },
});
