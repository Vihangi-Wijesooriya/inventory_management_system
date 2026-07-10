initTxn({
  resource: 'sales',
  partyResource: 'customers',
  partyField: 'customer_id',
  partyName: 'customer_name',
  dateField: 'sale_date',
  hasCost: false,
  labels: {
    partyLabel: 'Customer',
    noParty: 'Walk-in',
    deleteConfirm: 'Delete this sale? Stock will be restored.',
    deleted: 'Sale deleted, stock restored.',
    saved: 'Sale completed.',
  },
});
