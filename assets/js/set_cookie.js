const parsedUrl = new URL(window.location.href);
let refCode = parsedUrl.searchParams.get("ref");
if(refCode !== null)
{
    Cookies.set('wc_commission_ref_code', refCode, { expires: 1, path: '/' });
}