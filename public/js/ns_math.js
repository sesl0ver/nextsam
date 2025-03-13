class nsMath
{
    constructor ()
    {
        this._value = undefined;
        return this;
    }

    get number ()
    {
        let value = this._value instanceof Big ? this._value.toNumber() : undefined;
        this._value = undefined;
        return value;
    }

    get string ()
    {
        let value = this._value instanceof Big ? this._value.toString() : undefined;
        this._value = undefined;
        return value;
    }

    get integer ()
    {
        let value = this._value instanceof Big ? this._value.toString() : undefined;
        this._value = undefined;
        return parseInt(value, 10);
    }

    get number_format ()
    {
        let value = this._value instanceof Big ? this._value.toString() : undefined;
        this._value = undefined;
        return ns_util.numberFormat(parseInt(value, 10));
    }

    set (n)
    {
        this._value = new Big(n)
        return this;
    }

    plus (n)
    {
        this._value = new Big(this._value).plus(n);
        return this;
    }

    minus (n)
    {
        this._value = new Big(this._value).minus(n);
        return this;
    }

    mul (n)
    {
        this._value = new Big(this._value).times(n);
        return this;
    }

    div (n)
    {
        this._value = new Big(this._value).div(n);
        return this;
    }

    mod (n)
    {
        this._value = new Big(this._value).mod(n);
        return this;
    }

    pow (n)
    {
        this._value = new Big(this._value).pow(n);
        return this;
    }

    abs ()
    {
        this._value = new Big(this._value).abs();
        return this;
    }

    neg ()
    {
        this._value = new Big(this._value).neg();
        return this;
    }

    sqrt ()
    {
        this._value = new Big(this._value).sqrt();
        return this;
    }

    compare (n)
    {
        return new Big(this._value).cmp(n); // 1, -1, 0
    }

    eq (n)
    {
        return new Big(this._value).eq(n);
    }

    gt (n)
    {
        return new Big(this._value).gt(n);
    }

    gte (n)
    {
        return new Big(this._value).gte(n);
    }

    lt (n)
    {
        return new Big(this._value).lt(n);
    }

    lte (n)
    {
        return new Big(this._value).lte(n);
    }

    toFixed (n)
    {
        return parseFloat(new Big(this._value).toFixed(n));
    }

    toPrecision (n)
    {
        return new Big(this._value).toPrecision(n);
    }

    valueOf ()
    {
        return new Big(this._value).valueOf();
    }
}