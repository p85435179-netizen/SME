"""Optional integration test against the running XAMPP app; standard library only."""
import argparse
import http.cookiejar
import json
import urllib.error
import urllib.parse
import urllib.request

parser = argparse.ArgumentParser()
parser.add_argument('--url', default='http://localhost/SME/')
parser.add_argument('--password', default='SmeDemo2026!')
args = parser.parse_args()
base = args.url.rstrip('/') + '/api.php?route='

def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def call(opener, route, method='GET', data=None, csrf=''):
    request = urllib.request.Request(
        base + urllib.parse.quote(route, safe=''),
        data=json.dumps(data).encode() if data is not None else None,
        method=method,
        headers={'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
    )
    try:
        with opener.open(request, timeout=15) as response:
            return response.status, json.load(response)
    except urllib.error.HTTPError as error:
        try:
            return error.code, json.load(error)
        except ValueError:
            raise RuntimeError('The server did not return JSON. Check the localhost URL and PHP/Apache configuration.')

def expect(opener, route, status, method='GET', data=None, csrf=''):
    code, result = call(opener, route, method, data, csrf)
    assert code == status, f'{method} {route}: expected {status}, received {code}: {result}'
    print(f'PASS {method} {route}: {code}')
    return result

def login(role):
    opener = client()
    expect(opener, 'login', 200, 'POST', {'email': f'{role}@sme.local', 'password': args.password})
    state = expect(opener, 'workspace', 200)
    assert state['user']['role'] == role
    return opener, state

record_id = None
owner = None
owner_state = None
try:
    anonymous = client()
    expect(anonymous, 'workspace', 401)
    expect(anonymous, 'login', 401, 'POST', {'email': "' OR 1=1 --", 'password': 'invalid'})
    owner, owner_state = login('owner')
    accountant, accountant_state = login('accountant')
    admin, admin_state = login('admin')
    assert admin_state['users'], 'Admin user list should be available'
    assert owner_state['users'] == [], 'Owner must not receive the admin user list'
    csrf = owner_state['csrf']
    income = next(c for c in owner_state['categories'] if c['type'] == 'income')
    expense = next(c for c in owner_state['categories'] if c['type'] == 'expense')
    payload = {'type': 'income', 'category_id': income['id'], 'description': 'Temporary XAMPP integration check', 'amount': '123.45', 'record_date': '2026-10-09'}
    expect(owner, 'records', 403, 'POST', payload)
    expect(owner, 'records', 400, 'POST', {**payload, 'amount': '-1'}, csrf)
    expect(owner, 'records', 400, 'POST', {**payload, 'amount': '1.001'}, csrf)
    expect(owner, 'records', 400, 'POST', {**payload, 'record_date': '2026-02-30'}, csrf)
    expect(owner, 'records', 400, 'POST', {**payload, 'category_id': expense['id']}, csrf)
    expect(owner, 'records', 400, 'POST', {**payload, 'description': 'x'}, csrf)
    expect(owner, 'users', 403, 'POST', {}, csrf)
    created = expect(owner, 'records', 200, 'POST', payload, csrf)
    record_id = created['id']
    state = expect(owner, 'workspace', 200)
    record = next(r for r in state['records'] if r['id'] == record_id)
    assert record['amount_cents'] == 12345
    assert record['category'] == income['name'] and record['creator'] == owner_state['user']['name'], 'JOIN-derived names should match'
    expect(accountant, 'records', 403, 'POST', payload, accountant_state['csrf'])
    expect(accountant, f'records/{record_id}', 403, 'PUT', payload, accountant_state['csrf'])
    expect(accountant, f'records/{record_id}', 403, 'DELETE', {}, accountant_state['csrf'])
    expect(owner, f'records/{record_id}/review', 403, 'POST', {}, csrf)
    expect(accountant, f'records/{record_id}/review', 200, 'POST', {}, accountant_state['csrf'])
    state = expect(owner, 'workspace', 200)
    assert next(r for r in state['records'] if r['id'] == record_id)['status'] == 'reviewed'
    expect(owner, f'records/{record_id}', 200, 'PUT', {**payload, 'amount': '240.00'}, csrf)
    state = expect(owner, 'workspace', 200)
    record = next(r for r in state['records'] if r['id'] == record_id)
    assert record['amount_cents'] == 24000 and record['status'] == 'pending' and record['reviewed_by'] is None
    expect(owner, f'records/{record_id}', 200, 'DELETE', {}, csrf)
    deleted_id = record_id
    record_id = None
    state = expect(owner, 'workspace', 200)
    assert not any(r['id'] == deleted_id for r in state['records'])
    assert any(a['action'] == f'Deleted record #{deleted_id}' for a in state['activity'])
    expect(owner, 'logout', 200, 'POST', {}, csrf)
    expect(owner, 'workspace', 401)
    print('\nAll API integration checks passed. Temporary record removed; audit entries retained.')
finally:
    if record_id is not None and owner is not None and owner_state is not None:
        code, result = call(owner, f'records/{record_id}', 'DELETE', {}, owner_state['csrf'])
        if code == 200:
            print(f'Cleanup: removed temporary record #{record_id}.')
        else:
            print(f'Cleanup could not remove temporary record #{record_id}: {result}')
