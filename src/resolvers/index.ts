import Resolver from '@forge/resolver';

const resolver = new Resolver();

resolver.define('storeLastGeneration', req => {
    console.log(req);
});

export const handler = resolver.getDefinitions();
