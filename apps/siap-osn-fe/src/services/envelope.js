// Envelope sukses Laravel: { message, data }. Service hanya meneruskan `data`
// supaya store/view tidak perlu menulis `res.data.data` di mana-mana.
export const unwrap = (res) => res?.data?.data
